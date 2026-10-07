<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Unit\View;

use ArrayIterator;
use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\Exception\ExceptionInterface;
use Contenir\Resource\Laminas\Mvc\Exception\InvalidResourceIdException;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Manager\InMemoryResourceManager;
use Contenir\Resource\Laminas\Mvc\View\Helper\ResourceHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * The id forms the resource helper takes; which resource a list finds is
 * checked against a real database in the integration suite.
 */
#[Group('unit')]
final class ResourceHelperTest extends TestCase
{
    /**
     * @return array<string, array{mixed, string}>
     */
    public static function invalidIdProvider(): array
    {
        return [
            'a word'                    => ['about', '"about"'],
            'trailing characters'       => ['12abc', '"12abc"'],
            'leading characters'        => ['x12', '"x12"'],
            'a trailing newline'        => ["12\n", "\"12\n\""],
            'a negative number string'  => ['-1', '"-1"'],
            'a decimal string'          => ['1.5', '"1.5"'],
            'surrounding whitespace'    => [' 5', '" 5"'],
            'a list with a word'        => [['4', 'about'], '"about"'],
            'a list with a float'       => [[1.5], 'float'],
            'a list with a bool'        => [[true], 'bool'],
            'a list with null'          => [[null], 'null'],
            'a list with a nested list' => [[[4]], 'array'],
            'a list with an object'     => [[new stdClass()], 'stdClass'],
        ];
    }

    /**
     * @return array<string, array{iterable<mixed>}>
     */
    public static function noIdListProvider(): array
    {
        return [
            'an empty list'      => [[]],
            'only blank strings' => [['', '']],
            'an empty iterator'  => [new ArrayIterator([])],
        ];
    }

    /**
     * @return array<string, array{int|string, int}>
     */
    public static function singleIdProvider(): array
    {
        return [
            'an int'                      => [12, 12],
            'a string of digits'          => ['12', 12],
            'leading zeros, as 1.x found' => ['007', 7],
            'zero, which finds nothing'   => ['0', 0],
            'an int that finds nothing'   => [-3, -3],
        ];
    }

    #[Test]
    public function aBlankIdFindsNothingWithoutALookup(): void
    {
        $manager = new InMemoryResourceManager(ResourceFactory::make());

        static::assertSame([null, []], [(new ResourceHelper($manager))(''), $manager->calls]);
    }

    #[Test]
    public function aListFindsTheFirstActiveResourceByIdAmongItsIds(): void
    {
        $about   = ResourceFactory::make();
        $manager = new InMemoryResourceManager($about);

        static::assertSame(
            [
                $about,
                [[
                    'findOneBy',
                    [
                        ['resourceId' => [9, 4, 7], 'status' => ResourceStatus::Active],
                        ['resourceId' => 'ASC'],
                    ],
                ]],
            ],
            [(new ResourceHelper($manager))(['9', 4, '', '7']), $manager->calls],
        );
    }

    /**
     * @param iterable<mixed> $ids
     */
    #[Test]
    #[DataProvider('noIdListProvider')]
    public function aListWithoutIdsFindsNothingWithoutALookup(iterable $ids): void
    {
        $manager = new InMemoryResourceManager(ResourceFactory::make());

        static::assertSame([null, []], [(new ResourceHelper($manager))($ids), $manager->calls]);
    }

    #[Test]
    public function anyIterableIsAList(): void
    {
        $manager = new InMemoryResourceManager();

        (new ResourceHelper($manager))(new ArrayIterator(['b' => '5', 'a' => '3']));

        static::assertSame(
            [[
                'findOneBy',
                [
                    ['resourceId' => [5, 3], 'status' => ResourceStatus::Active],
                    ['resourceId' => 'ASC'],
                ],
            ]],
            $manager->calls,
        );
    }

    #[Test]
    #[DataProvider('invalidIdProvider')]
    public function anythingElseIsRejected(mixed $resourceId, string $described): void
    {
        $this->expectException(InvalidResourceIdException::class);
        $this->expectExceptionMessage("A resource id must be an int or a string of digits, got {$described}");

        /** @var int|string|iterable<mixed> $resourceId */
        (new ResourceHelper(new InMemoryResourceManager()))($resourceId);
    }

    #[Test]
    #[DataProvider('singleIdProvider')]
    public function aSingleIdIsLookedUpAsAnInt(int|string $resourceId, int $expected): void
    {
        $manager = new InMemoryResourceManager();

        (new ResourceHelper($manager))($resourceId);

        static::assertSame([['findActive', $expected]], $manager->calls);
    }

    #[Test]
    public function theRejectionIsAContenirResourceException(): void
    {
        static::assertInstanceOf(ExceptionInterface::class, InvalidResourceIdException::forValue('about'));
    }
}

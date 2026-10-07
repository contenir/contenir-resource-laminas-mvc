<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Unit\View;

use Contenir\Resource\Core\Content\ResourceSummary;
use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Manager\InMemoryResourceManager;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Router\RecordingRouter;
use Contenir\Resource\Laminas\Mvc\Url\ResourceUrlGenerator;
use Contenir\Resource\Laminas\Mvc\View\Helper\ResourceContentHelper;
use Contenir\Resource\Laminas\Mvc\View\Helper\ResourceHelper;
use Contenir\Resource\Laminas\Mvc\View\Helper\ResourceUrlHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ViewHelpersTest extends TestCase
{
    /**
     * @return iterable<string, array{array<array-key, mixed>, ?int, list<array{string, mixed}>}>
     */
    public static function resourceIdLists(): iterable
    {
        yield 'a stored id list' => [['358'], 358, [['findActive', '358']]];
        yield 'the first id that resolves' => [
            [9, 3, '2', 358],
            2,
            [
                ['findActive', 9],
                ['findActive', 3],
                ['findActive', '2'],
            ],
        ];
        yield 'invalid entries are skipped' => [
            [null, [2], 0, -2, '0', '-2', '2x', '', 1.5, '358'],
            358,
            [['findActive', '358']],
        ];
        yield 'an empty list' => [[], null, []];
        yield 'no valid id' => [[0, '0', 'abc', null], null, []];
        yield 'no active resource' => [[9, '3'], null, [['findActive', 9], ['findActive', '3']]];
    }

    #[Test]
    public function theContentHelperSummarises(): void
    {
        static::assertSame('Hello', (new ResourceContentHelper(new ResourceSummary()))('<p>Hello</p>'));
    }

    #[Test]
    public function theContentHelperSummarisesNothingByDefault(): void
    {
        static::assertSame('', (new ResourceContentHelper(new ResourceSummary()))());
    }

    #[Test]
    public function theResourceHelperFindsActiveResources(): void
    {
        $about   = ResourceFactory::make();
        $manager = new InMemoryResourceManager($about);
        $helper  = new ResourceHelper($manager);

        static::assertSame(
            [
                [$about, $about, $about, $about],
                [
                    ['findActive',               1],
                    ['findActiveBySlug',         'about'],
                    ['findActiveByWorkflow',     'news'],
                    ['findActivePageByWorkflow', 'shop'],
                ],
            ],
            [
                [
                    $helper(1),
                    $helper->findBySlug('about'),
                    $helper->findByWorkflow('news'),
                    $helper->findActivePageByWorkflow('shop'),
                ],
                $manager->calls,
            ],
        );
    }

    /**
     * @param array<array-key, mixed> $ids
     * @param list<array{string, mixed}> $queried
     */
    #[Test]
    #[DataProvider('resourceIdLists')]
    public function theResourceHelperResolvesAListOfIds(array $ids, ?int $found, array $queried): void
    {
        $manager = new InMemoryResourceManager(
            ResourceFactory::make(2),
            ResourceFactory::make(3, status: ResourceStatus::Inactive),
            ResourceFactory::make(358),
        );
        $result = (new ResourceHelper($manager))($ids);

        static::assertSame([$found, $queried], [$result?->getId(), $manager->calls]);
    }

    #[Test]
    public function theResourceHelperReturnsItselfWithoutAnId(): void
    {
        $manager = new InMemoryResourceManager();
        $helper  = new ResourceHelper($manager);

        static::assertSame([$helper, []], [$helper(), $manager->calls]);
    }

    #[Test]
    public function theUrlHelperReturnsTheUrlAndTargetPair(): void
    {
        $helper = new ResourceUrlHelper(new ResourceUrlGenerator(
            new RecordingRouter(['page-1' => '/about']),
            new InMemoryResourceManager(ResourceFactory::make()),
        ));

        static::assertSame(
            [
                ['/about',              '_self'],
                ['https://example.com', '_blank'],
                [null,                  null],
            ],
            [$helper(1, target: '_self'), $helper(url: 'example.com'), $helper()],
        );
    }
}

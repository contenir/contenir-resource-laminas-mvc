<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Integration\View;

use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\MvcApplicationTrait;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\SqliteDatabaseTrait;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\TemporaryDirectoryTrait;
use Contenir\Resource\Laminas\Mvc\View\Helper\ResourceHelper;
use Laminas\View\HelperPluginManager;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The resource helper from a real application's ViewHelperManager, over
 * resources 4 (inactive), 7 and 9 (active) and 12 (archived).
 */
#[Group('integration')]
final class ResourceHelperTest extends TestCase
{
    use MvcApplicationTrait;
    use SqliteDatabaseTrait;
    use TemporaryDirectoryTrait;

    private ResourceHelper $helper;

    /**
     * @return array<string, array{int|string|list<int|string>, ?int}>
     */
    public static function lookupProvider(): array
    {
        return [
            'an active id'                   => ['9', 9],
            'an inactive id'                 => [4, null],
            'a list, by id not list order'   => [['9', '7'], 7],
            'a list, skipping inactive ids'  => [['9', '4', '7'], 7],
            'a list, skipping missing ids'   => [[3, '9'], 9],
            'a list, skipping blank entries' => [['', '9'], 9],
            'a list with only inactive ids'  => [['4', '12'], null],
            'a list with a single active id' => [['7'], 7],
        ];
    }

    /**
     * @param int|string|list<int|string> $resourceId
     */
    #[Test]
    #[DataProvider('lookupProvider')]
    public function itFindsTheFirstActiveResourceById(int|string|array $resourceId, ?int $expected): void
    {
        $resource = ($this->helper)($resourceId);

        static::assertSame(
            $expected,
            $resource instanceof AbstractResourceEntity ? $resource->getId() : $resource,
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpTemporaryDirectory();
        $this->setUpDatabase();
        $this->insertResource(['resource_id' => 4, 'slug' => 'four', 'active' => 'inactive']);
        $this->insertResource(['resource_id' => 7, 'slug' => 'seven']);
        $this->insertResource(['resource_id' => 9, 'slug' => 'nine']);
        $this->insertResource(['resource_id' => 12, 'slug' => 'twelve', 'active' => 'archived']);

        $helpers = $this->bootstrapApplication()->getServiceManager()->get('ViewHelperManager');
        static::assertInstanceOf(HelperPluginManager::class, $helpers);
        $helper = $helpers->get('resource');
        static::assertInstanceOf(ResourceHelper::class, $helper);
        $this->helper = $helper;
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->tearDownTemporaryDirectory();
    }
}

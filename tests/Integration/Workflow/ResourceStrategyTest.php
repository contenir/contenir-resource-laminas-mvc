<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Integration\Workflow;

use Contenir\Mvc\Workflow\Strategy\ResourceStrategyInterface;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Controller\PageController;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\MvcApplicationTrait;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\SqliteDatabaseTrait;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\TemporaryDirectoryTrait;
use Laminas\Router\Http\Literal;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function count;

/**
 * workflow-laminas-mvc's own ResourceStrategy over ResourceTreeAdapter and
 * the SQLite resource tree, as a site configures it.
 */
#[Group('integration')]
final class ResourceStrategyTest extends TestCase
{
    use MvcApplicationTrait;
    use SqliteDatabaseTrait;
    use TemporaryDirectoryTrait;

    #[Test]
    public function activeTopLevelPagesAndTheirDescendantsAreRouted(): void
    {
        static::assertSame(['page-1', 'page-2', 'page-3'], array_keys($this->strategy()->getRouteConfig()));
    }

    #[Test]
    public function aRouteCarriesThePrimaryKeysForTheListener(): void
    {
        static::assertSame(
            [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/about/team',
                    'defaults' => [
                        'controller'  => PageController::class,
                        'action'      => 'index',
                        'resource_id' => ['resourceId' => 2],
                    ],
                ],
            ],
            $this->strategy()->getRouteConfig()['page-2'] ?? null,
        );
    }

    #[Test]
    public function navigationShowsVisiblePagesWithTheirShortTitles(): void
    {
        $navigation = $this->strategy()->getNavigationConfig();

        static::assertSame(
            [
                'label'   => 'About',
                'route'   => 'page-1',
                'lastmod' => '2024-02-03 04:05:06',
                'visible' => true,
                'child'   => 'Team',
                'count'   => 1,
            ],
            [
                'label'   => $navigation[0]['label'] ?? null,
                'route'   => $navigation[0]['route'] ?? null,
                'lastmod' => $navigation[0]['lastmod'] ?? null,
                'visible' => $navigation[0]['visible'] ?? null,
                'child'   => $navigation[0]['pages'][0]['label'] ?? null,
                'count'   => count($navigation),
            ],
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpTemporaryDirectory();
        $this->setUpDatabase();
        $this->insertResource([
            'resource_id' => 1,
            'slug'        => 'about',
            'title'       => 'About us',
            'title_short' => 'About',
            'sequence'    => 1,
            'updated'     => '2024-02-03 04:05:06',
        ]);
        $this->insertResource(['resource_id' => 2, 'parent_id' => 1, 'slug' => 'about/team', 'title' => 'Team']);
        $this->insertResource([
            'resource_id' => 3,
            'slug'        => 'hidden',
            'title'       => 'Hidden',
            'sequence'    => 2,
            'visible'     => 0,
        ]);
        $this->insertResource(['resource_id' => 4, 'parent_id' => 3, 'slug' => 'hidden/child', 'title' => 'Child']);
        $this->insertResource([
            'resource_id' => 5,
            'slug'        => 'old',
            'title'       => 'Old',
            'sequence'    => 3,
            'active'      => 'inactive',
        ]);
        $this->insertResource([
            'resource_id'      => 6,
            'resource_type_id' => 'article',
            'slug'             => 'stray',
            'title'            => 'Stray',
        ]);
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->tearDownTemporaryDirectory();
    }

    private function strategy(): ResourceStrategyInterface
    {
        $strategy = $this->bootstrapApplication()->getServiceManager()->get(ResourceStrategyInterface::class);
        static::assertInstanceOf(ResourceStrategyInterface::class, $strategy);

        return $strategy;
    }
}

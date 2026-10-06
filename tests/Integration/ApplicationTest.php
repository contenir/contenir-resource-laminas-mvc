<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Integration;

use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Controller\PageController;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity\ImageResourceEntity;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity\SectionResourceEntity;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\MvcApplicationTrait;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\SqliteDatabaseTrait;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\TemporaryDirectoryTrait;
use Laminas\Cache\Storage\Adapter\Memory;
use Laminas\Router\Http\Segment;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_contains;
use function strstr;

/**
 * Requests through a real laminas-mvc Application: the workflow routes
 * built from the SQLite resource tree, the ResourceListener, the resource()
 * plugin and the view helpers in real templates.
 */
#[Group('integration')]
final class ApplicationTest extends TestCase
{
    use MvcApplicationTrait;
    use SqliteDatabaseTrait;
    use TemporaryDirectoryTrait;

    /**
     * @return array<string, array{string}>
     */
    public static function unpublishedStatusProvider(): array
    {
        return [
            'inactive' => ['inactive'],
            'pending'  => ['pending'],
            'archived' => ['archived'],
        ];
    }

    #[Test]
    public function anUnmatchedUrlIsNotFound(): void
    {
        static::assertSame(
            404,
            $this->dispatch($this->bootstrapApplication(), 'https://www.example.com/missing')->getStatusCode(),
        );
    }

    #[Test]
    public function aPathSegmentNamedResourceIdIsNotAResourceRoute(): void
    {
        $response = $this->dispatch(
            $this->bootstrapApplication(routes: [
                'item' => [
                    'type'    => Segment::class,
                    'options' => [
                        'route'    => '/item/:resource_id',
                        'defaults' => ['controller' => PageController::class, 'action' => 'index'],
                    ],
                ],
            ]),
            'https://www.example.com/item/1',
        );

        static::assertSame(
            [500, true],
            [
                $response->getStatusCode(),
                str_contains($response->getContent(), 'No resource was resolved for this request'),
            ],
        );
    }

    #[Test]
    #[DataProvider('unpublishedStatusProvider')]
    public function aResourceUnpublishedAfterRoutingIsNotFound(string $status): void
    {
        $routeCache = new Memory();
        $this->bootstrapApplication(routeCache: $routeCache);
        $this->pdo->exec("UPDATE resource SET active = '{$status}' WHERE resource_id = 1");

        $response = $this->dispatch(
            $this->bootstrapApplication(routeCache: $routeCache),
            'https://www.example.com/about',
        );

        static::assertSame(
            [404, false, true],
            [
                $response->getStatusCode(),
                str_contains($response->getContent(), '<h1>'),
                str_contains($response->getContent(), 'class="not-found"'),
            ],
        );
    }

    #[Test]
    public function aShareImageIsResolvedOnTheSite(): void
    {
        $this->pdo->exec("UPDATE resource SET description = '/img/a.jpg'");

        $content = $this->dispatch(
            $this->bootstrapApplication(['resource_entity' => ImageResourceEntity::class]),
            'https://www.example.com/about',
        )->getContent();

        static::assertStringContainsString(
            '<meta property="og&#x3A;image" content="https&#x3A;&#x2F;&#x2F;www.example.com&#x2F;img&#x2F;a.jpg">',
            $content,
        );
    }

    #[Test]
    public function aShareImageWithAnUnsafeSchemeIsDropped(): void
    {
        $this->pdo->exec("UPDATE resource SET description = 'javascript:alert(1)'");

        $content = $this->dispatch(
            $this->bootstrapApplication(['resource_entity' => ImageResourceEntity::class]),
            'https://www.example.com/about',
        )->getContent();

        static::assertSame(
            [false, false],
            [
                str_contains($content, 'og&#x3A;image'),
                str_contains(strstr($content, needle: '</head>', before_needle: true), 'javascript'),
            ],
        );
    }

    #[Test]
    public function childResourcesAreRouted(): void
    {
        $response = $this->dispatch($this->bootstrapApplication(), 'https://www.example.com/about/team');

        static::assertStringContainsString('<h1>Team</h1>', $response->getContent());
    }

    #[Test]
    public function metaOutputIsEscaped(): void
    {
        $content = $this->dispatch($this->bootstrapApplication(), 'https://www.example.com/about')->getContent();

        static::assertSame(
            [true, true, true, false],
            [
                str_contains($content, '<title>About &lt;us&gt;</title>'),
                str_contains($content, '<meta property="og&#x3A;title" content="About&#x20;&lt;us&gt;">'),
                str_contains($content, '<meta name="description" content="Fish&#x20;&amp;&#x20;&quot;chips&quot;">'),
                str_contains($content, '<us>'),
            ],
        );
    }

    #[Test]
    public function resourceContentRendersTheSectionPartial(): void
    {
        $content = $this->dispatch(
            $this->bootstrapApplication(['resource_entity' => SectionResourceEntity::class]),
            'https://www.example.com/about',
        )->getContent();

        static::assertSame(
            [true, true],
            [
                str_contains($content, '<p class="summary">Section heading about</p>'),
                str_contains($content, '<p class="by-slug">About &lt;us&gt;</p>'),
            ],
        );
    }

    #[Test]
    public function theCanonicalUrlDropsTheQueryString(): void
    {
        $content = $this->dispatch($this->bootstrapApplication(), 'https://www.example.com/about?utm=1')->getContent();

        static::assertStringContainsString('<p class="canonical">https://www.example.com/about</p>', $content);
    }

    #[Test]
    public function theCanonicalUrlIgnoresTheHostHeaderWhenABaseUrlIsConfigured(): void
    {
        $content = $this->dispatch(
            $this->bootstrapApplication(['base_url' => 'https://www.example.com']),
            'https://evil.test/about',
        )->getContent();

        static::assertSame(
            [true, false],
            [
                str_contains(
                    $content,
                    '<link href="https&#x3A;&#x2F;&#x2F;www.example.com&#x2F;about" rel="canonical">',
                ),
                str_contains($content, 'evil'),
            ],
        );
    }

    #[Test]
    public function theCanonicalUrlUsesTheRequestHostWithoutABaseUrl(): void
    {
        $content = $this->dispatch($this->bootstrapApplication(), 'https://other.test/about')->getContent();

        static::assertStringContainsString('<p class="canonical">https://other.test/about</p>', $content);
    }

    #[Test]
    public function theControllerReceivesTheRoutedResource(): void
    {
        $response = $this->dispatch($this->bootstrapApplication(), 'https://www.example.com/about');

        static::assertSame(
            [200, true, true, true, true],
            [
                $response->getStatusCode(),
                str_contains($response->getContent(), '<h1>About &lt;us&gt;</h1>'),
                str_contains($response->getContent(), '<p class="by-route">About &lt;us&gt;</p>'),
                str_contains($response->getContent(), '<p class="by-slug">About &lt;us&gt;</p>'),
                str_contains($response->getContent(), '<a class="self" href="&#x2F;about" target="_self">'),
            ],
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpTemporaryDirectory();
        $this->setUpDatabase();
        $this->insertResource([
            'resource_id'      => 1,
            'slug'             => 'about',
            'title'            => 'About <us>',
            'meta_description' => '<p>Fish &amp; "chips"</p>',
            'created'          => '2024-01-02 03:04:05',
            'updated'          => '2024-02-03 04:05:06',
        ]);
        $this->insertResource(['resource_id' => 2, 'parent_id' => 1, 'slug' => 'about/team', 'title' => 'Team']);
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->tearDownTemporaryDirectory();
    }
}

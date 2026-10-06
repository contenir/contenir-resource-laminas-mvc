<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Unit\Url;

use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\Url\ResourceLink;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Manager\InMemoryResourceManager;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Router\RecordingRouter;
use Contenir\Resource\Laminas\Mvc\Url\ResourceUrlGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ResourceUrlGeneratorTest extends TestCase
{
    /**
     * @return array<string, array{string, string|null}>
     */
    public static function externalUrlProvider(): array
    {
        return [
            'a bare host'     => ['example.com/page', 'https://example.com/page'],
            'an https URL'    => ['https://example.com', 'https://example.com'],
            'a mailto link'   => ['mailto:a@example.com', 'mailto:a@example.com'],
            'a root path'     => ['/contact', '/contact'],
            'javascript'      => ['javascript:alert(1)', null],
            'a data URL'      => ['data:text/html,x', null],
            'only whitespace' => [' ', null],
        ];
    }

    private static function generator(RecordingRouter $router, InMemoryResourceManager $manager): ResourceUrlGenerator
    {
        return new ResourceUrlGenerator($router, $manager);
    }

    #[Test]
    #[DataProvider('externalUrlProvider')]
    public function anEditorEnteredUrlOpensInANewWindowWhenSafe(string $url, ?string $expected): void
    {
        $link = self::generator(new RecordingRouter(), new InMemoryResourceManager())
            ->generate(
                url: $url,
                target: '_self',
            );

        static::assertEquals(new ResourceLink($expected, '_blank'), $link);
    }

    #[Test]
    public function anEmptyIdFallsBackToTheUrl(): void
    {
        $manager = new InMemoryResourceManager(ResourceFactory::make());

        $link = self::generator(new RecordingRouter(), $manager)->generate('', 'example.com');

        static::assertEquals([new ResourceLink('https://example.com', '_blank'), []], [$link, $manager->calls]);
    }

    #[Test]
    public function anEntityLinksToItsRoute(): void
    {
        $router  = new RecordingRouter(['page-1' => '/about']);
        $manager = new InMemoryResourceManager();

        $link = self::generator($router, $manager)->generate(ResourceFactory::make(), target: '_self');

        static::assertEquals(
            [new ResourceLink('/about', '_self'), [[[], ['name' => 'page-1']]], []],
            [$link, $router->calls, $manager->calls],
        );
    }

    #[Test]
    public function anIdLinksToTheActiveResourcesRoute(): void
    {
        $manager = new InMemoryResourceManager(ResourceFactory::make());

        $link = self::generator(new RecordingRouter(['page-1' => '/about']), $manager)->generate('1');

        static::assertEquals([new ResourceLink('/about'), [['findActive', '1']]], [$link, $manager->calls]);
    }

    #[Test]
    public function anInactiveResourceHasNoUrl(): void
    {
        $link = self::generator(
            new RecordingRouter(['page-1' => '/about']),
            new InMemoryResourceManager(ResourceFactory::make(status: ResourceStatus::Inactive)),
        )
            ->generate(1, target: '_self');

        static::assertEquals(new ResourceLink(null, '_self'), $link);
    }

    #[Test]
    public function anUnsavedEntityHasNoUrl(): void
    {
        $router = new RecordingRouter(['page-1' => '/about']);

        $link = self::generator($router, new InMemoryResourceManager())->generate(ResourceFactory::make(null));

        static::assertEquals([new ResourceLink(), []], [$link, $router->calls]);
    }

    #[Test]
    public function aResourceTakesPrecedenceOverTheUrl(): void
    {
        $link = self::generator(new RecordingRouter(['page-1' => '/about']), new InMemoryResourceManager())
            ->generate(ResourceFactory::make(), 'example.com');

        static::assertEquals(new ResourceLink('/about'), $link);
    }

    #[Test]
    public function aResourceWithoutARouteHasNoUrl(): void
    {
        $link = self::generator(new RecordingRouter(), new InMemoryResourceManager())
            ->generate(ResourceFactory::make());

        static::assertEquals(new ResourceLink(), $link);
    }

    #[Test]
    public function nothingGivesNoUrlButKeepsTheTarget(): void
    {
        $link = self::generator(new RecordingRouter(), new InMemoryResourceManager())
            ->generate(
                url: '',
                target: '_top',
            );

        static::assertEquals(new ResourceLink(null, '_top'), $link);
    }
}

<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Unit\Controller\Plugin;

use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\Exception\MissingResourceException;
use Contenir\Resource\Core\Metadata\PageMetadata;
use Contenir\Resource\Laminas\Mvc\Controller\Plugin\ResourcePlugin;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Controller\PageController;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Manager\InMemoryResourceManager;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\DispatchableInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ResourcePluginTest extends TestCase
{
    /**
     * @return array<string, array{int|string|array<array-key, mixed>}>
     */
    public static function resourceIdProvider(): array
    {
        return [
            'an int'                     => [1],
            'a string'                   => ['1'],
            'the route primary keys'     => [['resourceId' => 1]],
            'the route keys as a string' => [['resourceId' => '1']],
        ];
    }

    /**
     * @return array<string, array{array<array-key, mixed>}>
     */
    public static function unusableKeysProvider(): array
    {
        return [
            'no resource id' => [['id' => 1]],
            'a float id'     => [['resourceId' => 1.0]],
            'a null id'      => [['resourceId' => null]],
        ];
    }

    private static function controllerWith(?AbstractResourceEntity $resource, ?PageMetadata $metadata): PageController
    {
        $event = new MvcEvent();
        $event->setParam(AbstractResourceEntity::class, $resource);
        $event->setParam(PageMetadata::class, $metadata);
        $controller = new PageController();
        $controller->setEvent($event);

        return $controller;
    }

    #[Test]
    public function aMissingResourceIsNullWhenNotThrowing(): void
    {
        static::assertNull((new ResourcePlugin(new InMemoryResourceManager()))(5, throwException: false));
    }

    #[Test]
    public function aMissingResourceThrowsByDefault(): void
    {
        $this->expectException(MissingResourceException::class);
        $this->expectExceptionMessage('Resource not found');

        (new ResourcePlugin(new InMemoryResourceManager()))(5);
    }

    #[Test]
    public function aMissingResourceThrowsFromResourceByDefault(): void
    {
        $this->expectException(MissingResourceException::class);
        $this->expectExceptionMessage('Resource not found');

        (new ResourcePlugin(new InMemoryResourceManager()))->resource(5);
    }

    #[Test]
    public function anInactiveResourceIsNotFound(): void
    {
        $plugin = new ResourcePlugin(new InMemoryResourceManager(ResourceFactory::make(
            status: ResourceStatus::Pending,
        )));

        static::assertNull($plugin->resource(1, throwException: false));
    }

    #[Test]
    public function itHasNoControllerAtFirst(): void
    {
        static::assertNull((new ResourcePlugin(new InMemoryResourceManager()))->getController());
    }

    #[Test]
    public function itHoldsItsController(): void
    {
        $plugin     = new ResourcePlugin(new InMemoryResourceManager());
        $controller = $this->createStub(DispatchableInterface::class);
        $plugin->setController($controller);

        static::assertSame($controller, $plugin->getController());
    }

    #[Test]
    public function metadataIsNullWithoutAnEventAwareController(): void
    {
        $plugin = new ResourcePlugin(new InMemoryResourceManager());
        $plugin->setController($this->createStub(DispatchableInterface::class));

        static::assertNull($plugin->metadata());
    }

    #[Test]
    public function routedAndMetadataReadTheControllersEvent(): void
    {
        $about    = ResourceFactory::make();
        $metadata = new PageMetadata('https://s.test/');
        $plugin   = new ResourcePlugin(new InMemoryResourceManager());
        $plugin->setController(self::controllerWith($about, $metadata));

        static::assertSame([$about, $metadata], [$plugin->routed(), $plugin->metadata()]);
    }

    #[Test]
    public function routedFailsWhenTheRequestIsNotAResourceRoute(): void
    {
        $plugin = new ResourcePlugin(new InMemoryResourceManager());
        $plugin->setController(self::controllerWith(null, null));

        $this->expectException(MissingResourceException::class);
        $this->expectExceptionMessage('route the request through a resource workflow');

        $plugin->routed();
    }

    #[Test]
    public function routedFailsWithoutAnEventAwareController(): void
    {
        $plugin = new ResourcePlugin(new InMemoryResourceManager());
        $plugin->setController($this->createStub(DispatchableInterface::class));

        $this->expectException(MissingResourceException::class);
        $this->expectExceptionMessage('No resource was resolved for this request; the controller has no MvcEvent');

        $plugin->routed();
    }

    /**
     * @param int|string|array<array-key, mixed> $resourceId
     */
    #[Test]
    #[DataProvider('resourceIdProvider')]
    public function theActiveResourceIsFoundByIdOrRouteKeys(int|string|array $resourceId): void
    {
        $about   = ResourceFactory::make();
        $manager = new InMemoryResourceManager($about);

        static::assertSame($about, (new ResourcePlugin($manager))($resourceId));
    }

    /**
     * @param array<array-key, mixed> $keys
     */
    #[Test]
    #[DataProvider('unusableKeysProvider')]
    public function unusableRouteKeysAreNotLookedUp(array $keys): void
    {
        $manager = new InMemoryResourceManager(ResourceFactory::make());

        static::assertSame(
            [null, []],
            [(new ResourcePlugin($manager))->resource($keys, throwException: false), $manager->calls],
        );
    }

    #[Test]
    public function withoutAnIdItIsTheResourceManager(): void
    {
        $manager = new InMemoryResourceManager();

        static::assertSame($manager, (new ResourcePlugin($manager))());
    }
}

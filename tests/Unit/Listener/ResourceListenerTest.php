<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Unit\Listener;

use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\Metadata\PageMetadata;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Laminas\Mvc\Listener\ResourceListener;
use Contenir\Resource\Laminas\Mvc\ResourceParam;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Event\EventTarget;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Manager\InMemoryResourceManager;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\MvcEventTrait;
use Laminas\EventManager\EventManager;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ResourceListenerTest extends TestCase
{
    use MvcEventTrait;

    /**
     * @return array<string, array{array<string, mixed>|null}>
     */
    public static function notAResourceRouteProvider(): array
    {
        return [
            'no route match'               => [null],
            'no resource_id parameter'     => [['controller' => 'x']],
            'a resource_id path segment'   => [['resource_id' => '1']],
            'keys without the resource id' => [['resource_id' => ['id' => 1]]],
        ];
    }

    /**
     * @return array<string, array{int|string}>
     */
    public static function resourceIdProvider(): array
    {
        return [
            'an int'             => [1],
            'a string of digits' => ['1'],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function unusableIdProvider(): array
    {
        return [
            'null'   => [null],
            'array'  => [[1]],
            'float'  => [1.0],
            'object' => [new PageMetadata('https://s.test/')],
        ];
    }

    private static function listener(InMemoryResourceManager $manager): ResourceListener
    {
        return new ResourceListener($manager, new PageMetadataBuilder(baseUrl: 'https://www.example.com'));
    }

    #[Test]
    public function aMissingResourceIsAnUnmatchedRoute(): void
    {
        $event = self::routeEvent(['resource_id' => ['resourceId' => 99]]);
        $event->setTarget(new EventTarget());

        self::listener(new InMemoryResourceManager(ResourceFactory::make()))->onRoute($event);

        static::assertSame(
            [MvcEvent::EVENT_DISPATCH_ERROR, Application::ERROR_ROUTER_NO_MATCH, null],
            [$event->getName(), $event->getError(), ResourceParam::resource($event)],
        );
    }

    #[Test]
    public function anInactiveResourceIsAnUnmatchedRoute(): void
    {
        $event = self::routeEvent(['resource_id' => ['resourceId' => 1]]);

        self::listener(new InMemoryResourceManager(ResourceFactory::make(status: ResourceStatus::Inactive)))
            ->onRoute($event);

        static::assertSame(
            [Application::ERROR_ROUTER_NO_MATCH, null],
            [$event->getError(), ResourceParam::resource($event)],
        );
    }

    #[Test]
    public function anUnmatchedResourceTriggersTheDispatchErrorOnTheTarget(): void
    {
        $events = new EventManager();
        $seen   = [];
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, static function (MvcEvent $event) use (&$seen): string {
            $seen[] = $event->getError();

            return 'first';
        });
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, static fn(): string => 'last', -1);
        $event = self::routeEvent(['resource_id' => ['resourceId' => 99]]);
        $event->setTarget(new EventTarget($events));

        $result = self::listener(new InMemoryResourceManager())->onRoute($event);

        static::assertSame(['last', [Application::ERROR_ROUTER_NO_MATCH]], [$result, $seen]);
    }

    #[Test]
    public function anUnmatchedResourceWithoutAnEventsCapableTargetReturnsNothing(): void
    {
        $event = self::routeEvent(['resource_id' => ['resourceId' => 99]]);
        $event->setTarget('not an application');

        static::assertNull(self::listener(new InMemoryResourceManager())->onRoute($event));
    }

    #[Test]
    public function itAttachesToTheRouteEventAfterTheRouter(): void
    {
        $events = new EventManager();
        $seen   = [];
        foreach ([-99 => 'before', -101 => 'after'] as $priority => $name) {
            $events->attach(
                MvcEvent::EVENT_ROUTE,
                static function (MvcEvent $event) use (&$seen, $name): void {
                    $seen[$name] = null !== ResourceParam::resource($event);
                },
                $priority,
            );
        }

        self::listener(new InMemoryResourceManager(ResourceFactory::make()))->attach($events);
        $events->triggerEvent(self::routeEvent(['resource_id' => ['resourceId' => 1]]));

        static::assertSame(['before' => false, 'after' => true], $seen);
    }

    #[Test]
    public function itCanBeDetached(): void
    {
        $events   = new EventManager();
        $listener = self::listener(new InMemoryResourceManager(ResourceFactory::make()));
        $listener->attach($events);
        $listener->detach($events);
        $event = self::routeEvent(['resource_id' => ['resourceId' => 1]]);

        $events->triggerEvent($event);

        static::assertNull(ResourceParam::resource($event));
    }

    /**
     * @param array<string, mixed>|null $params
     */
    #[Test]
    #[DataProvider('notAResourceRouteProvider')]
    public function otherRoutesPassThroughUntouched(?array $params): void
    {
        $manager = new InMemoryResourceManager(ResourceFactory::make());
        $event   = self::routeEvent($params);

        $result = self::listener($manager)->onRoute($event);

        static::assertSame(
            [null, [], MvcEvent::EVENT_ROUTE, '', null, null],
            [
                $result,
                $manager->calls,
                $event->getName(),
                $event->getError(),
                ResourceParam::resource($event),
                ResourceParam::metadata($event),
            ],
        );
    }

    #[Test]
    #[DataProvider('resourceIdProvider')]
    public function theActiveResourceAndItsMetadataAreSetOnTheEvent(int|string $resourceId): void
    {
        $about   = ResourceFactory::make();
        $manager = new InMemoryResourceManager($about);
        $event   = self::routeEvent(['resource_id' => ['resourceId' => $resourceId]], 'https://evil.test/about?x=1');

        $result = self::listener($manager)->onRoute($event);

        static::assertEquals(
            [null, [['findActive', $resourceId]], $about, new PageMetadata('https://www.example.com/about', 'About')],
            [$result, $manager->calls, ResourceParam::resource($event), ResourceParam::metadata($event)],
        );
    }

    #[Test]
    public function theMetadataOfANonHttpRequestHasTheRootPath(): void
    {
        $event = self::routeEvent(['resource_id' => ['resourceId' => 1]]);
        $event->setRequest(new Request());

        self::listener(new InMemoryResourceManager(ResourceFactory::make()))->onRoute($event);

        static::assertSame('https://www.example.com/', ResourceParam::metadata($event)?->url);
    }

    #[Test]
    #[DataProvider('unusableIdProvider')]
    public function unusableIdsAreNotLookedUp(mixed $resourceId): void
    {
        $manager = new InMemoryResourceManager(ResourceFactory::make());
        $event   = self::routeEvent(['resource_id' => ['resourceId' => $resourceId]]);

        self::listener($manager)->onRoute($event);

        static::assertSame([[], Application::ERROR_ROUTER_NO_MATCH], [$manager->calls, $event->getError()]);
    }
}

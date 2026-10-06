<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Unit;

use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Laminas\Mvc\ConfigProvider;
use Contenir\Resource\Laminas\Mvc\Listener\ResourceListener;
use Contenir\Resource\Laminas\Mvc\Module;
use Contenir\Resource\Laminas\Mvc\ResourceParam;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Manager\InMemoryResourceManager;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\MvcEventTrait;
use Laminas\EventManager\EventManager;
use Laminas\Mvc\ApplicationInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[Group('unit')]
final class ModuleTest extends TestCase
{
    use MvcEventTrait;

    #[Test]
    public function attachListenerAttachesToTheRouteEvent(): void
    {
        $events = new EventManager();
        (new Module())->attachListener($events, new ResourceListener(
            new InMemoryResourceManager(ResourceFactory::make()),
            new PageMetadataBuilder(),
        ));
        $event = self::routeEvent(['resource_id' => ['resourceId' => 1]]);

        $events->triggerEvent($event);

        static::assertNotNull(ResourceParam::resource($event));
    }

    #[Test]
    public function bootstrapAttachesTheListenerAfterTheRouter(): void
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

        $this->bootstrap($events, new ResourceListener(
            new InMemoryResourceManager(ResourceFactory::make()),
            new PageMetadataBuilder(),
        ));
        $events->triggerEvent(self::routeEvent(['resource_id' => ['resourceId' => 1]]));

        static::assertSame(['before' => false, 'after' => true], $seen);
    }

    #[Test]
    public function bootstrapRejectsAListenerOfTheWrongType(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Service "Contenir\Resource\Laminas\Mvc\Listener\ResourceListener" must be a '
                . 'Contenir\Resource\Laminas\Mvc\Listener\ResourceListener, got stdClass',
        );

        $this->bootstrap(new EventManager(), new stdClass());
    }

    #[Test]
    public function theConfigIsTheConfigProviders(): void
    {
        static::assertSame((new ConfigProvider())(), (new Module())->getConfig());
    }

    private function bootstrap(EventManager $events, object $listener): void
    {
        $services    = new ServiceManager(['services' => [ResourceListener::class => $listener]]);
        $application = $this->createStub(ApplicationInterface::class);
        $application->method('getServiceManager')->willReturn($services);
        $application->method('getEventManager')->willReturn($events);
        $event = new MvcEvent();
        $event->setApplication($application);

        (new Module())->onBootstrap($event);
    }
}

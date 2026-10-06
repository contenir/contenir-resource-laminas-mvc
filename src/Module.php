<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc;

use Contenir\Resource\Core\Container\ServiceLocator;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Laminas\Mvc\Listener\ResourceListener;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Psr\Container\ContainerExceptionInterface;

/**
 * laminas-mvc module: registers the configuration and, on bootstrap,
 * attaches the ResourceListener to the application's route event.
 *
 * Add it to the modules after Laminas\Router, Contenir\Db\Model and
 * Contenir\Mvc\Workflow.
 *
 * @api
 */
final class Module
{
    public function attachListener(EventManagerInterface $events, ResourceListener $listener): void
    {
        $listener->attach($events, ResourceListener::PRIORITY);
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return (new ConfigProvider())();
    }

    /**
     * @throws ConfigurationException When the listener service has the wrong type.
     * @throws ContainerExceptionInterface
     */
    public function onBootstrap(MvcEvent $event): void
    {
        $application = $event->getApplication();

        $this->attachListener(
            $application->getEventManager(),
            ServiceLocator::get($application->getServiceManager(), ResourceListener::class, ResourceListener::class),
        );
    }
}

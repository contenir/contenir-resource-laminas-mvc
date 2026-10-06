<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Container;

use Contenir\Resource\Core\Container\ServiceLocator;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Laminas\Mvc\Url\ResourceUrlGenerator;
use Laminas\Router\RouteStackInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Builds the ResourceUrlGenerator with the application's "router" service.
 *
 * @api
 */
final class ResourceUrlGeneratorFactory
{
    /**
     * The laminas-mvc router service.
     */
    public const string ROUTER = 'router';

    /**
     * @throws ConfigurationException When a service has the wrong type.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ResourceUrlGenerator
    {
        return new ResourceUrlGenerator(
            ServiceLocator::get($container, self::ROUTER, RouteStackInterface::class),
            ServiceLocator::get($container, ResourceManagerInterface::class, ResourceManagerInterface::class),
        );
    }
}

<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Container;

use Contenir\Resource\Core\Container\ServiceLocator;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Laminas\Mvc\Controller\Plugin\ResourcePlugin;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @api
 */
final class ResourcePluginFactory
{
    /**
     * @throws ConfigurationException When the resource manager service has the wrong type.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ResourcePlugin
    {
        return new ResourcePlugin(
            ServiceLocator::get($container, ResourceManagerInterface::class, ResourceManagerInterface::class),
        );
    }
}

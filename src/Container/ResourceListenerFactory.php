<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Container;

use Contenir\Resource\Core\Container\ServiceLocator;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Laminas\Mvc\Listener\ResourceListener;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @api
 */
final class ResourceListenerFactory
{
    /**
     * @throws ConfigurationException When a service has the wrong type.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ResourceListener
    {
        return new ResourceListener(
            ServiceLocator::get($container, ResourceManagerInterface::class, ResourceManagerInterface::class),
            ServiceLocator::get($container, PageMetadataBuilder::class, PageMetadataBuilder::class),
        );
    }
}

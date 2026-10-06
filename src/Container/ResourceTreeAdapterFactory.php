<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Container;

use Contenir\Resource\Core\Container\ServiceLocator;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Repository\ResourceRepository;
use Contenir\Resource\Laminas\Mvc\Workflow\ResourceTreeAdapter;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @api
 */
final class ResourceTreeAdapterFactory
{
    /**
     * @throws ConfigurationException When the repository service has the wrong type.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ResourceTreeAdapter
    {
        return new ResourceTreeAdapter(
            ServiceLocator::get($container, ResourceRepository::class, ResourceRepository::class),
        );
    }
}

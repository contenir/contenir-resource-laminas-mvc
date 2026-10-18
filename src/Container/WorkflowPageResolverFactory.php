<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Container;

use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Resource\Core\Container\ServiceLocator;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Laminas\Mvc\Preview\WorkflowPageResolver;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @api
 */
final class WorkflowPageResolverFactory
{
    /**
     * @throws ConfigurationException When the workflow manager service has the wrong type.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): WorkflowPageResolver
    {
        return new WorkflowPageResolver(ServiceLocator::get($container, PluginManager::class, PluginManager::class));
    }
}

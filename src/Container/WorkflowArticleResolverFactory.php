<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Container;

use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Resource\Core\Container\ServiceLocator;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Laminas\Mvc\Preview\WorkflowArticleResolver;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function str_starts_with;
use function strlen;
use function substr;

/**
 * Builds a {@see WorkflowArticleResolver} for any service named after it, a dot and the listing
 * page's workflow (`WorkflowArticleResolver::class . '.news'`), so each CMS section can name its
 * listing page in configuration alone.
 *
 * @api
 */
final class WorkflowArticleResolverFactory implements AbstractFactoryInterface
{
    private const string PREFIX = WorkflowArticleResolver::class . '.';

    /**
     * @param string $requestedName
     */
    #[Override]
    public function canCreate(ContainerInterface $container, $requestedName): bool
    {
        return str_starts_with($requestedName, self::PREFIX) && strlen($requestedName) > strlen(self::PREFIX);
    }

    /**
     * @param string                    $requestedName
     * @param array<array-key, mixed>|null $options
     *
     * @throws ConfigurationException When the workflow or resource manager service has the wrong type.
     * @throws ContainerExceptionInterface
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): WorkflowArticleResolver {
        return new WorkflowArticleResolver(
            ServiceLocator::get($container, PluginManager::class, PluginManager::class),
            ServiceLocator::get($container, ResourceManagerInterface::class, ResourceManagerInterface::class),
            substr($requestedName, strlen(self::PREFIX)),
        );
    }
}

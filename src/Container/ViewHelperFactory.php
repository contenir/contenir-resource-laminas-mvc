<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Container;

use Contenir\Resource\Core\Container\ServiceLocator;
use Contenir\Resource\Core\Content\ResourceSummary;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Laminas\Mvc\Url\ResourceUrlGenerator;
use Contenir\Resource\Laminas\Mvc\View\Helper\ResourceContentHelper;
use Contenir\Resource\Laminas\Mvc\View\Helper\ResourceHelper;
use Contenir\Resource\Laminas\Mvc\View\Helper\ResourceMetaHelper;
use Contenir\Resource\Laminas\Mvc\View\Helper\ResourceUrlHelper;
use Laminas\Http\Request;
use Laminas\View\Helper\HeadLink;
use Laminas\View\Helper\HeadMeta;
use Laminas\View\Helper\HeadTitle;
use Laminas\View\HelperPluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function sprintf;

/**
 * Builds the four view helpers from the application's services.
 * resourceMeta writes to the head helpers of the "ViewHelperManager", the
 * plugin manager the view renderer uses, and reads the "Request" service.
 *
 * @api
 */
final class ViewHelperFactory
{
    /**
     * The laminas-mvc view helper manager service.
     */
    public const string HELPERS = 'ViewHelperManager';

    /**
     * The laminas-mvc request service.
     */
    public const string REQUEST = 'Request';

    /**
     * @throws ConfigurationException When a service has the wrong type.
     * @throws ContainerExceptionInterface
     */
    private static function metaHelper(ContainerInterface $container): ResourceMetaHelper
    {
        $helpers = ServiceLocator::get($container, self::HELPERS, HelperPluginManager::class);

        return new ResourceMetaHelper(
            ServiceLocator::get($helpers, HeadTitle::class, HeadTitle::class),
            ServiceLocator::get($helpers, HeadLink::class, HeadLink::class),
            ServiceLocator::get($helpers, HeadMeta::class, HeadMeta::class),
            ServiceLocator::get($container, PageMetadataBuilder::class, PageMetadataBuilder::class),
            ServiceLocator::get($container, self::REQUEST, Request::class),
        );
    }

    /**
     * @throws ConfigurationException When a service has the wrong type or the helper is unknown.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
    ): ResourceHelper|ResourceContentHelper|ResourceMetaHelper|ResourceUrlHelper {
        return match ($requestedName) {
            ResourceHelper::class => new ResourceHelper(
                ServiceLocator::get($container, ResourceManagerInterface::class, ResourceManagerInterface::class),
            ),
            ResourceContentHelper::class => new ResourceContentHelper(
                ServiceLocator::get($container, ResourceSummary::class, ResourceSummary::class),
            ),
            ResourceUrlHelper::class => new ResourceUrlHelper(
                ServiceLocator::get($container, ResourceUrlGenerator::class, ResourceUrlGenerator::class),
            ),
            ResourceMetaHelper::class    => self::metaHelper($container),
            default                      => throw new ConfigurationException(sprintf(
                'No view helper "%s" is built by this factory',
                $requestedName,
            )),
        };
    }
}

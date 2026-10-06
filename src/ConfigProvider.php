<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc;

use Contenir\Resource\Core\ConfigProvider as CoreConfigProvider;
use Contenir\Resource\Laminas\Mvc\Container\PartialSectionRendererFactory;
use Contenir\Resource\Laminas\Mvc\Container\ResourceListenerFactory;
use Contenir\Resource\Laminas\Mvc\Container\ResourcePluginFactory;
use Contenir\Resource\Laminas\Mvc\Container\ResourceTreeAdapterFactory;
use Contenir\Resource\Laminas\Mvc\Container\ResourceUrlGeneratorFactory;
use Contenir\Resource\Laminas\Mvc\Container\ViewHelperFactory;
use Contenir\Resource\Laminas\Mvc\Content\PartialSectionRenderer;
use Contenir\Resource\Laminas\Mvc\Controller\Plugin\ResourcePlugin;
use Contenir\Resource\Laminas\Mvc\Listener\ResourceListener;
use Contenir\Resource\Laminas\Mvc\Url\ResourceUrlGenerator;
use Contenir\Resource\Laminas\Mvc\View\Helper\ResourceContentHelper;
use Contenir\Resource\Laminas\Mvc\View\Helper\ResourceHelper;
use Contenir\Resource\Laminas\Mvc\View\Helper\ResourceMetaHelper;
use Contenir\Resource\Laminas\Mvc\View\Helper\ResourceUrlHelper;
use Contenir\Resource\Laminas\Mvc\Workflow\ResourceTreeAdapter;

/**
 * The laminas-mvc configuration, returned by Module::getConfig().
 *
 * "service_manager" holds contenir-resource's own services (repositories,
 * resource manager, metadata builder, summary), which that package
 * registers under "dependencies" only, plus this package's listener, URL
 * generator, section renderer and workflow adapter. Controller plugin and
 * view helper names are those of contenir-resource 1.x.
 *
 * The only "contenir_resource" default is "section_renderer", so that
 * resourceContent renders section content through a partial as 1.x did.
 * Routing is switched on by the application's own "workflow_manager" config.
 *
 * @psalm-type ServiceConfig = array{
 *     aliases: array<class-string, class-string>,
 *     invokables: array<class-string, class-string>,
 *     factories: array<class-string, class-string>
 * }
 * @psalm-type PluginConfig = array{aliases: array<string, class-string>, factories: array<class-string, class-string>}
 *
 * @api
 */
final class ConfigProvider
{
    /**
     * @return PluginConfig
     */
    public function getControllerPluginConfig(): array
    {
        return [
            'aliases'   => [
                'resource' => ResourcePlugin::class,
                'Resource' => ResourcePlugin::class,
            ],
            'factories' => [
                ResourcePlugin::class => ResourcePluginFactory::class,
            ],
        ];
    }

    /**
     * contenir-resource's services, with this package's added.
     *
     * @return ServiceConfig
     */
    public function getDependencies(): array
    {
        $core = (new CoreConfigProvider())->getDependencies();

        return [
            'aliases'    => $core['aliases'],
            'invokables' => $core['invokables'],
            'factories'  => [
                ...$core['factories'],
                PartialSectionRenderer::class => PartialSectionRendererFactory::class,
                ResourceListener::class       => ResourceListenerFactory::class,
                ResourceTreeAdapter::class    => ResourceTreeAdapterFactory::class,
                ResourceUrlGenerator::class   => ResourceUrlGeneratorFactory::class,
            ],
        ];
    }

    /**
     * @return array{section_renderer: class-string<PartialSectionRenderer>}
     */
    public function getResourceConfig(): array
    {
        return [
            'section_renderer' => PartialSectionRenderer::class,
        ];
    }

    /**
     * @return PluginConfig
     */
    public function getViewHelperConfig(): array
    {
        return [
            'aliases'   => [
                'resource'        => ResourceHelper::class,
                'Resource'        => ResourceHelper::class,
                'resourceContent' => ResourceContentHelper::class,
                'ResourceContent' => ResourceContentHelper::class,
                'resourceMeta'    => ResourceMetaHelper::class,
                'ResourceMeta'    => ResourceMetaHelper::class,
                'resourceUrl'     => ResourceUrlHelper::class,
                'ResourceUrl'     => ResourceUrlHelper::class,
            ],
            'factories' => [
                ResourceHelper::class        => ViewHelperFactory::class,
                ResourceContentHelper::class => ViewHelperFactory::class,
                ResourceMetaHelper::class    => ViewHelperFactory::class,
                ResourceUrlHelper::class     => ViewHelperFactory::class,
            ],
        ];
    }

    /**
     * @return array{
     *     service_manager: ServiceConfig,
     *     controller_plugins: PluginConfig,
     *     view_helpers: PluginConfig,
     *     contenir_resource: array{section_renderer: class-string<PartialSectionRenderer>},
     * }
     */
    public function __invoke(): array
    {
        return [
            'service_manager'    => $this->getDependencies(),
            'controller_plugins' => $this->getControllerPluginConfig(),
            'view_helpers'       => $this->getViewHelperConfig(),
            'contenir_resource'  => $this->getResourceConfig(),
        ];
    }
}

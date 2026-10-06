<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Unit;

use Contenir\Resource\Core\ConfigProvider as CoreConfigProvider;
use Contenir\Resource\Core\ResourceManager;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Laminas\Mvc\ConfigProvider;
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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function itAddsItsServicesToTheCoreServices(): void
    {
        $core = (new CoreConfigProvider())->getDependencies();

        static::assertSame(
            [
                'aliases'    => $core['aliases'],
                'invokables' => $core['invokables'],
                'factories'  => [
                    ...$core['factories'],
                    PartialSectionRenderer::class => PartialSectionRendererFactory::class,
                    ResourceListener::class       => ResourceListenerFactory::class,
                    ResourceTreeAdapter::class    => ResourceTreeAdapterFactory::class,
                    ResourceUrlGenerator::class   => ResourceUrlGeneratorFactory::class,
                ],
            ],
            (new ConfigProvider())->getDependencies(),
        );
    }

    #[Test]
    public function itExposesEachSection(): void
    {
        $provider = new ConfigProvider();
        $config   = $provider();

        static::assertSame(
            [$config['controller_plugins'], $config['view_helpers'], $config['contenir_resource']],
            [$provider->getControllerPluginConfig(), $provider->getViewHelperConfig(), $provider->getResourceConfig()],
        );
    }

    #[Test]
    public function itKeepsTheCoreResourceManagerAlias(): void
    {
        static::assertSame(
            ResourceManager::class,
            (new ConfigProvider())->getDependencies()['aliases'][ResourceManagerInterface::class] ?? null,
        );
    }

    #[Test]
    public function itRegistersTheLaminasMvcConfigKeys(): void
    {
        $provider = new ConfigProvider();

        static::assertSame(
            [
                'service_manager'    => $provider->getDependencies(),
                'controller_plugins' => [
                    'aliases'   => ['resource' => ResourcePlugin::class, 'Resource' => ResourcePlugin::class],
                    'factories' => [ResourcePlugin::class => ResourcePluginFactory::class],
                ],
                'view_helpers'       => [
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
                ],
                'contenir_resource'  => ['section_renderer' => PartialSectionRenderer::class],
            ],
            $provider(),
        );
    }
}

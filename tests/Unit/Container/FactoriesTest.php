<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Unit\Container;

use Contenir\Db\Model\EntityManager;
use Contenir\Resource\Core\Content\ResourceSummary;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Core\Repository\ResourceRepository;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Laminas\Mvc\Container\PartialSectionRendererFactory;
use Contenir\Resource\Laminas\Mvc\Container\ResourceListenerFactory;
use Contenir\Resource\Laminas\Mvc\Container\ResourcePluginFactory;
use Contenir\Resource\Laminas\Mvc\Container\ResourceTreeAdapterFactory;
use Contenir\Resource\Laminas\Mvc\Container\ResourceUrlGeneratorFactory;
use Contenir\Resource\Laminas\Mvc\Container\ViewHelperFactory;
use Contenir\Resource\Laminas\Mvc\ResourceParam;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Container\ArrayContainer;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity\SectionResourceEntity;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Manager\InMemoryResourceManager;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Router\RecordingRouter;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\View\RecordingRenderer;
use Contenir\Resource\Laminas\Mvc\Tests\Trait\MvcEventTrait;
use Contenir\Resource\Laminas\Mvc\Url\ResourceUrlGenerator;
use Contenir\Resource\Laminas\Mvc\View\Helper\ResourceContentHelper;
use Contenir\Resource\Laminas\Mvc\View\Helper\ResourceHelper;
use Contenir\Resource\Laminas\Mvc\View\Helper\ResourceMetaHelper;
use Contenir\Resource\Laminas\Mvc\View\Helper\ResourceUrlHelper;
use Contenir\Resource\Laminas\Mvc\Workflow\ResourceTreeAdapter;
use Laminas\Http\Request;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\HelperPluginManager;
use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[Group('unit')]
final class FactoriesTest extends TestCase
{
    use MvcEventTrait;

    #[Test]
    public function theListenerResolvesWithTheManagerAndBuilder(): void
    {
        $about    = ResourceFactory::make();
        $listener = (new ResourceListenerFactory())(new ArrayContainer([
            ResourceManagerInterface::class => new InMemoryResourceManager($about),
            PageMetadataBuilder::class      => new PageMetadataBuilder(),
        ]));
        $event = self::routeEvent(['resource_id' => ['resourceId' => 1]]);

        $listener->onRoute($event);

        static::assertSame(
            [$about, 'https://www.example.com/about'],
            [ResourceParam::resource($event), ResourceParam::metadata($event)?->url],
        );
    }

    #[Test]
    public function theMetaHelperNeedsAnHttpRequest(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Service "Request" must be a Laminas\Http\Request, got stdClass');

        (new ViewHelperFactory())(new ArrayContainer([
            'ViewHelperManager'        => new HelperPluginManager(new ServiceManager()),
            PageMetadataBuilder::class => new PageMetadataBuilder(),
            'Request'                  => new stdClass(),
        ]), ResourceMetaHelper::class);
    }

    #[Test]
    public function thePluginUsesTheResourceManager(): void
    {
        $manager = new InMemoryResourceManager();

        static::assertSame(
            $manager,
            (new ResourcePluginFactory())(new ArrayContainer([ResourceManagerInterface::class => $manager]))(),
        );
    }

    #[Test]
    public function theSectionRendererRejectsARendererOfTheWrongType(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Service "ViewRenderer" must be a Laminas\View\Renderer\RendererInterface');

        (new PartialSectionRendererFactory())(new ArrayContainer(['ViewRenderer' => new stdClass()]));
    }

    #[Test]
    public function theSectionRendererRejectsATemplateThatIsNotAName(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Config "contenir_resource.section_template" must be a non-empty string, got int',
        );

        (new PartialSectionRendererFactory())(new ArrayContainer([
            'config'       => ['contenir_resource' => ['section_template' => 5]],
            'ViewRenderer' => new RecordingRenderer(),
        ]));
    }

    #[Test]
    public function theSectionRendererSummarisesThroughResourceSummary(): void
    {
        $renderer = (new PartialSectionRendererFactory())(new ArrayContainer([
            'ViewRenderer' => new RecordingRenderer(),
        ]));

        static::assertSame(
            'application/component/_section',
            (new ResourceSummary($renderer))->summarise(new SectionResourceEntity()),
        );
    }

    #[Test]
    public function theSectionRendererUsesTheConfiguredTemplate(): void
    {
        $renderer = (new PartialSectionRendererFactory())(new ArrayContainer([
            'config'       => ['contenir_resource' => ['section_template' => 'site/section']],
            'ViewRenderer' => new RecordingRenderer(),
        ]));

        static::assertSame('<div>site/section</div>', $renderer->render(new SectionResourceEntity()));
    }

    #[Test]
    public function theTreeAdapterWrapsTheRepository(): void
    {
        static::assertInstanceOf(
            ResourceTreeAdapter::class,
            (new ResourceTreeAdapterFactory())(new ArrayContainer([ResourceRepository::class => $this->repository()])),
        );
    }

    #[Test]
    public function theUrlGeneratorUsesTheRouterAndManager(): void
    {
        $generator = (new ResourceUrlGeneratorFactory())(new ArrayContainer([
            'router'                        => new RecordingRouter(['page-1' => '/about']),
            ResourceManagerInterface::class => new InMemoryResourceManager(ResourceFactory::make()),
        ]));

        static::assertSame('/about', $generator->generate(1)->url);
    }

    #[Test]
    public function theViewHelperFactoryRejectsOtherNames(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('No view helper "headTitle" is built by this factory');

        (new ViewHelperFactory())(new ArrayContainer(), requestedName: 'headTitle');
    }

    #[Test]
    public function theViewHelpersAreBuiltFromTheApplicationServices(): void
    {
        $about     = ResourceFactory::make();
        $container = new ArrayContainer([
            ResourceManagerInterface::class => new InMemoryResourceManager($about),
            ResourceSummary::class          => new ResourceSummary(),
            ResourceUrlGenerator::class     => new ResourceUrlGenerator(
                new RecordingRouter(['page-1' => '/about']),
                new InMemoryResourceManager($about),
            ),
            'ViewHelperManager'             => new HelperPluginManager(new ServiceManager()),
            PageMetadataBuilder::class      => new PageMetadataBuilder(),
            'Request'                       => new Request(),
        ]);
        $factory = new ViewHelperFactory();

        $resource = $factory($container, ResourceHelper::class);
        $content  = $factory($container, ResourceContentHelper::class);
        $url      = $factory($container, ResourceUrlHelper::class);

        static::assertSame(
            [$about, 'Hi', ['/about', null], true],
            [
                $resource instanceof ResourceHelper ? $resource(1) : null,
                $content instanceof ResourceContentHelper ? $content('<b>Hi</b>') : null,
                $url instanceof ResourceUrlHelper ? $url(1) : null,
                $factory($container, ResourceMetaHelper::class) instanceof ResourceMetaHelper,
            ],
        );
    }

    private function repository(): ResourceRepository
    {
        return new ResourceRepository(new EntityManager($this->createStub(AdapterInterface::class)));
    }
}

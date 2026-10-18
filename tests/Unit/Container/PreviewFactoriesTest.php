<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Unit\Container;

use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Laminas\Mvc\Container\WorkflowArticleResolverFactory;
use Contenir\Resource\Laminas\Mvc\Container\WorkflowPageResolverFactory;
use Contenir\Resource\Laminas\Mvc\Preview\WorkflowArticleResolver;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Container\ArrayContainer;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Controller\PageController;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Manager\ListResourceManager;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Preview\PreviewRecords;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Workflow\PageControllerWorkflow;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The preview resolvers built from their service names.
 */
#[Group('unit')]
final class PreviewFactoriesTest extends TestCase
{
    /**
     * @return array<string, array{string, bool}>
     */
    public static function serviceNames(): array
    {
        return [
            'a listing workflow after the dot' => [WorkflowArticleResolver::class . '.news', true],
            'the dot alone'                    => [WorkflowArticleResolver::class . '.', false],
            'the resolver itself'              => [WorkflowArticleResolver::class, false],
            'something else'                   => [PageController::class . '.news', false],
        ];
    }

    #[Test]
    public function buildsAnArticleResolverForTheListingItsNameEndsWith(): void
    {
        $listing           = ResourceFactory::make(105);
        $listing->workflow = 'news';
        $resolver          = (new WorkflowArticleResolverFactory())($this->container([
            ResourceManagerInterface::class => new ListResourceManager([$listing]),
        ]), WorkflowArticleResolver::class . '.news');

        $target = $resolver->resolve(PreviewRecords::unsaved(['slug' => 'late-checkout']));

        static::assertSame(['resourceId' => 105], $target->params['resource_id'] ?? null);
    }

    #[Test]
    public function buildsThePageResolver(): void
    {
        $resolver = (new WorkflowPageResolverFactory())($this->container());

        static::assertSame(
            PageController::class,
            $resolver->resolve(PreviewRecords::saved(101, ['title' => 'About']))->handler,
        );
    }

    #[Test]
    #[DataProvider('serviceNames')]
    public function createsArticleResolversByNameOnly(string $name, bool $creates): void
    {
        static::assertSame($creates, (new WorkflowArticleResolverFactory())->canCreate($this->container(), $name));
    }

    /**
     * @param array<string, mixed> $services
     */
    private function container(array $services = []): ArrayContainer
    {
        return new ArrayContainer([
            PluginManager::class => new PluginManager(new ServiceManager(), [
                'aliases'   => ['page' => PageControllerWorkflow::class, 'news' => PageControllerWorkflow::class],
                'factories' => [PageControllerWorkflow::class => InvokableFactory::class],
            ]),
            ...$services,
        ]);
    }
}

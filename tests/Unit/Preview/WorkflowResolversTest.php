<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Unit\Preview;

use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Preview\InvalidPreview;
use Contenir\Preview\PreviewRecord;
use Contenir\Resource\Laminas\Mvc\Preview\WorkflowArticleResolver;
use Contenir\Resource\Laminas\Mvc\Preview\WorkflowController;
use Contenir\Resource\Laminas\Mvc\Preview\WorkflowPageResolver;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Controller\ArticleController;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Controller\PageController;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Manager\ListResourceManager;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Preview\PreviewRecords;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Workflow\ArticleControllerWorkflow;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Workflow\ControllerlessWorkflow;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Workflow\ForeignWorkflow;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Workflow\PageControllerWorkflow;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Previews of pages and article-like records resolved to the controllers their workflows name.
 */
#[Group('unit')]
final class WorkflowResolversTest extends TestCase
{
    /**
     * @return array<string, array{PreviewRecord}>
     */
    public static function recordsWithoutASlug(): array
    {
        return [
            'none'   => [PreviewRecords::saved(201, ['title' => 'Untitled'])],
            'empty'  => [PreviewRecords::saved(201, ['slug' => ''])],
            'a list' => [PreviewRecords::saved(201, ['slug' => ['a', 'b']])],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function workflowsWithoutAController(): array
    {
        return [
            'one the site does not have'   => ['missing'],
            'one with no controller'       => ['controllerless'],
            'one written to the interface' => ['foreign'],
        ];
    }

    #[Test]
    public function anArticleIsShownOnItsListingPagesDetailView(): void
    {
        $listing           = ResourceFactory::make(105, title: 'News');
        $listing->workflow = 'news';
        $target            = (new WorkflowArticleResolver(
            $this->workflows(),
            new ListResourceManager([$listing]),
            'news',
        ))->resolve(PreviewRecords::unsaved(['slug' => 'late-checkout']));

        static::assertSame(
            [
                ArticleController::class,
                ['action' => 'view', 'resource_id' => ['resourceId' => 105], 'slug' => 'late-checkout'],
            ],
            [$target->handler, $target->params],
        );
    }

    #[Test]
    public function anArticleWithoutAListingPageIsNotPreviewed(): void
    {
        $this->assertRefused(
            'The site has no page listing records like this one.',
            fn() => (new WorkflowArticleResolver($this->workflows(), new ListResourceManager(), 'news'))
                ->resolve(PreviewRecords::saved(201, ['slug' => 'late-checkout'])),
        );
    }

    #[Test]
    #[DataProvider('recordsWithoutASlug')]
    public function anArticleWithoutASlugIsNotPreviewed(PreviewRecord $record): void
    {
        $this->assertRefused(
            'This record needs a URL before it can be previewed.',
            fn() => (new WorkflowArticleResolver($this->workflows(), new ListResourceManager(), 'news'))->resolve(
                $record,
            ),
        );
    }

    #[Test]
    public function aPageNotSavedYetIsNotPreviewed(): void
    {
        $this->assertRefused(
            'This page can be previewed once it has been saved.',
            fn() => (new WorkflowPageResolver($this->workflows()))->resolve(PreviewRecords::unsaved([
                'title' => 'New',
            ])),
        );
    }

    #[Test]
    public function aPageWithoutATemplateIsAPlainPage(): void
    {
        $target = (new WorkflowPageResolver($this->workflows()))->resolve(PreviewRecords::saved(101, [
            'workflow' => '',
        ]));

        static::assertSame(PageController::class, $target->handler);
    }

    #[Test]
    #[DataProvider('workflowsWithoutAController')]
    public function aWorkflowThatNamesNoControllerIsNotPreviewed(string $workflow): void
    {
        $this->assertRefused(
            'This record uses a template the site does not have.',
            fn() => WorkflowController::of($this->workflows(), $workflow),
        );
    }

    #[Test]
    public function thePageIsShownAtItsTemplatesControllerWithItsId(): void
    {
        $target = (new WorkflowPageResolver($this->workflows()))->resolve(PreviewRecords::saved(101, [
            'workflow' => 'news',
        ]));

        static::assertSame(
            [ArticleController::class, ['action' => 'index', 'resource_id' => ['resourceId' => '101']]],
            [$target->handler, $target->params],
        );
    }

    /**
     * @param callable(): mixed $resolve
     */
    private function assertRefused(string $reason, callable $resolve): void
    {
        try {
            $resolve();
            static::fail('The preview was resolved.');
        } catch (InvalidPreview $refusal) {
            static::assertSame([404, $reason], [$refusal->status, $refusal->getMessage()]);
        }
    }

    private function workflows(): PluginManager
    {
        return new PluginManager(new ServiceManager(), [
            'aliases'   => [
                'page'           => PageControllerWorkflow::class,
                'news'           => ArticleControllerWorkflow::class,
                'controllerless' => ControllerlessWorkflow::class,
                'foreign'        => ForeignWorkflow::class,
            ],
            'factories' => [
                PageControllerWorkflow::class    => InvokableFactory::class,
                ArticleControllerWorkflow::class => InvokableFactory::class,
                ControllerlessWorkflow::class    => InvokableFactory::class,
                ForeignWorkflow::class           => InvokableFactory::class,
            ],
        ]);
    }
}

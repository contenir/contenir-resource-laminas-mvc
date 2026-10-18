<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Workflow;

use Contenir\Mvc\Workflow\Workflow\AbstractArticleWorkflow;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Controller\ArticleController;

/**
 * A listing page's workflow routing to ArticleController, as a site's news page registers one.
 */
final class ArticleControllerWorkflow extends AbstractArticleWorkflow
{
    protected ?string $controller = ArticleController::class;
}

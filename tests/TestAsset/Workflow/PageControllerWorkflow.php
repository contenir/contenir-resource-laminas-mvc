<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Workflow;

use Contenir\Mvc\Workflow\Workflow\AbstractPageWorkflow;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Controller\PageController;

/**
 * A page workflow routing to PageController, as a site registers one.
 */
final class PageControllerWorkflow extends AbstractPageWorkflow
{
    protected ?string $controller = PageController::class;
}

<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Workflow;

use Contenir\Mvc\Workflow\Workflow\WorkflowInterface;
use Override;

/**
 * A workflow written against the interface alone, which names no controller.
 */
final class ForeignWorkflow implements WorkflowInterface
{
    #[Override]
    public function getNavigationConfig(): array
    {
        return [];
    }

    #[Override]
    public function getRouteConfig(): array
    {
        return [];
    }

    #[Override]
    public function getRouteId(): string
    {
        return 'foreign';
    }

    #[Override]
    public function getRoutePath(): string
    {
        return '/foreign';
    }
}

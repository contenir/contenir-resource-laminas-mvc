<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Preview;

use Contenir\Mvc\Workflow\Exception\RuntimeException;
use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Mvc\Workflow\Workflow\AbstractWorkflow;
use Contenir\Preview\InvalidPreview;
use Psr\Container\ContainerExceptionInterface;

/**
 * The controller a workflow (the CMS's Template field) dispatches its pages to.
 *
 * @internal
 */
final class WorkflowController
{
    /**
     * @throws InvalidPreview When the site has no such workflow, or it names no controller
     */
    public static function of(PluginManager $workflows, string $workflow): string
    {
        try {
            /** @var mixed $instance */
            $instance = $workflows->get($workflow);

            return $instance instanceof AbstractWorkflow
                ? $instance->getRouteController()
                : throw InvalidPreview::notPreviewable('This record uses a template the site does not have.');
        } catch (ContainerExceptionInterface|RuntimeException) {
            throw InvalidPreview::notPreviewable('This record uses a template the site does not have.');
        }
    }
}

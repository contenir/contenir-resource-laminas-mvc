<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Preview;

use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Preview\InvalidPreview;
use Contenir\Preview\PreviewRecord;
use Contenir\Preview\Resolver\PreviewResolverInterface;
use Contenir\Preview\Resolver\PreviewTarget;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Override;

use function is_string;

/**
 * Previews a page at the controller its workflow (the CMS's Template field) names, with the
 * page's id as its route would carry it. The page is dispatched without routing, so a changed
 * slug or template shows as it will once saved. Name it as the resolver for the CMS section that
 * edits the site's pages:
 *
 *     'contenir_preview' => ['resolvers' => ['site.page.home' => WorkflowPageResolver::class]],
 *
 * @api
 */
final readonly class WorkflowPageResolver implements PreviewResolverInterface
{
    public function __construct(
        private PluginManager $workflows,
    ) {}

    /**
     * @throws InvalidPreview When the page has not been saved, or uses a template the site lacks
     */
    #[Override]
    public function resolve(PreviewRecord $record): PreviewTarget
    {
        $id = $record->keys['resource_id'] ?? throw InvalidPreview::notPreviewable(
            'This page can be previewed once it has been saved.',
        );
        $workflow = $record->value('workflow');

        return PreviewTarget::handler(
            WorkflowController::of($this->workflows, is_string($workflow) && '' !== $workflow ? $workflow : 'page'),
            ['action' => 'index', 'resource_id' => [AbstractResourceEntity::PRIMARY_KEY => $id]],
        );
    }
}

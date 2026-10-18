<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Preview;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Preview\InvalidPreview;
use Contenir\Preview\PreviewRecord;
use Contenir\Preview\Resolver\PreviewResolverInterface;
use Contenir\Preview\Resolver\PreviewTarget;
use Contenir\Resource\Core\Exception\MissingResourceException;
use Contenir\Resource\Core\ResourceManagerInterface;
use Override;

use function is_string;

/**
 * Previews an article-like record (a news article, an event, a package) on its detail page: the
 * view action of the page that lists records of its kind, found by that page's workflow, at the
 * slug the editor has given it. A record not saved yet previews too. Name it for each section by
 * the listing page's workflow, after a dot:
 *
 *     'contenir_preview' => ['resolvers' => [
 *         'library.articles.article' => WorkflowArticleResolver::class . '.news',
 *         'library.articles.event'   => WorkflowArticleResolver::class . '.event',
 *     ]],
 *
 * @api
 */
final readonly class WorkflowArticleResolver implements PreviewResolverInterface
{
    public function __construct(
        private PluginManager $workflows,
        private ResourceManagerInterface $resources,
        private string $listing,
    ) {}

    /**
     * @throws DbModelException
     * @throws InvalidPreview When the record has no slug, or the site no page listing its kind
     * @throws MissingResourceException Never for a listing page the site has stored
     */
    #[Override]
    public function resolve(PreviewRecord $record): PreviewTarget
    {
        $slug = $record->value('slug');
        if (! is_string($slug) || '' === $slug) {
            throw InvalidPreview::notPreviewable('This record needs a URL before it can be previewed.');
        }

        $page = $this->resources->findActivePageByWorkflow($this->listing)
            ?? throw InvalidPreview::notPreviewable('The site has no page listing records like this one.');

        return PreviewTarget::handler(WorkflowController::of($this->workflows, $this->listing), [
            'action'      => 'view',
            'resource_id' => $page->getPrimaryKeys(),
            'slug'        => $slug,
        ]);
    }
}

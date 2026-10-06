<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\View\Helper;

use Contenir\Resource\Core\Content\ResourceSummary;

/**
 * View helper "resourceContent": a plain-text summary of a resource or of
 * some content (see ResourceSummary). Escape the result.
 *
 * @api
 */
final readonly class ResourceContentHelper
{
    public function __construct(
        private ResourceSummary $summary,
    ) {}

    public function __invoke(mixed $content = null): string
    {
        return $this->summary->summarise($content);
    }
}

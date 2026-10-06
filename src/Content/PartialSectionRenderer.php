<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Content;

use Contenir\Resource\Core\Content\SectionAwareInterface;
use Contenir\Resource\Core\Content\SectionRendererInterface;
use Laminas\View\Renderer\RendererInterface;
use Override;

/**
 * Renders section content through the laminas-view renderer, as the 1.x
 * resourceContent helper's partial did: the template receives "section"
 * (the section content) and "resource".
 *
 * @api
 */
final readonly class PartialSectionRenderer implements SectionRendererInterface
{
    /**
     * The template 1.x rendered.
     */
    public const string DEFAULT_TEMPLATE = 'application/component/_section';

    public function __construct(
        private RendererInterface $renderer,
        private string $template = self::DEFAULT_TEMPLATE,
    ) {}

    #[Override]
    public function render(SectionAwareInterface $resource): string
    {
        return $this->renderer->render($this->template, [
            'section'  => $resource->getSection(),
            'resource' => $resource,
        ]);
    }
}

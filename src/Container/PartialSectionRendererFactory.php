<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Container;

use Contenir\Resource\Core\Container\ConfigReader;
use Contenir\Resource\Core\Container\ServiceLocator;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Laminas\Mvc\Content\PartialSectionRenderer;
use Laminas\View\Renderer\RendererInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Builds the PartialSectionRenderer with the "ViewRenderer" service and the
 * "contenir_resource.section_template" template name (default
 * "application/component/_section", the partial 1.x rendered).
 *
 * @api
 */
final class PartialSectionRendererFactory
{
    /**
     * The laminas-mvc view renderer service.
     */
    public const string RENDERER = 'ViewRenderer';

    /**
     * @throws ConfigurationException When section_template is not a template name or the renderer has the wrong type.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): PartialSectionRenderer
    {
        return new PartialSectionRenderer(
            ServiceLocator::get($container, self::RENDERER, RendererInterface::class),
            ConfigReader::fromContainer($container)->string(
                'section_template',
                PartialSectionRenderer::DEFAULT_TEMPLATE,
            ),
        );
    }
}

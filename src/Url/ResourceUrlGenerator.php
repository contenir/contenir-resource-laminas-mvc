<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Url;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Exception\MissingResourceException;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Core\Url\ExternalUrl;
use Contenir\Resource\Core\Url\ResourceLink;
use Laminas\Router\Exception\ExceptionInterface as RouterException;
use Laminas\Router\RouteStackInterface;

/**
 * Builds links to resources (through their workflow route) or to an
 * editor-entered URL, the way the 1.x ResourceUrl helper did.
 *
 * @api
 */
final readonly class ResourceUrlGenerator
{
    public function __construct(
        private RouteStackInterface $router,
        private ResourceManagerInterface $resources,
    ) {}

    /**
     * A link to the resource (an entity, or the id of an active resource),
     * keeping $target; or else, when $url is given, to that URL (normalised,
     * see ExternalUrl) in a new window. The link's url is null when the
     * resource is not found or has no route, or the URL is rejected.
     *
     * @throws DbModelException
     */
    public function generate(
        AbstractResourceEntity|int|string|null $resource = null,
        ?string $url = null,
        ?string $target = null,
    ): ResourceLink {
        if ($resource instanceof AbstractResourceEntity) {
            return new ResourceLink($this->routeUrl($resource), $target);
        }

        if (null !== $resource && '' !== $resource) {
            $found = $this->resources->findActive($resource);

            return new ResourceLink(null === $found ? null : $this->routeUrl($found), $target);
        }

        if (null !== $url && '' !== $url) {
            return new ResourceLink(ExternalUrl::normalise($url), '_blank');
        }

        return new ResourceLink(target: $target);
    }

    /**
     * The URL of the resource's route, or null when it has none (a resource
     * whose workflow registers no route, or an unsaved entity).
     */
    private function routeUrl(AbstractResourceEntity $resource): ?string
    {
        try {
            return (string) $this->router->assemble(options: ['name' => $resource->getRouteName()]);
        } catch (MissingResourceException|RouterException) {
            return null;
        }
    }
}

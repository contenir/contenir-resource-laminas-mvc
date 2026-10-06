<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Controller\Plugin;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Exception\MissingResourceException;
use Contenir\Resource\Core\Metadata\PageMetadata;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Laminas\Mvc\ResourceParam;
use Laminas\Mvc\Controller\Plugin\PluginInterface;
use Laminas\Mvc\InjectApplicationEventInterface;
use Laminas\Stdlib\DispatchableInterface;
use Override;

use function is_array;
use function is_int;
use function is_string;

/**
 * Controller plugin "resource", as in contenir-resource 1.x:
 * $this->resource() is the resource manager, and $this->resource($id) the
 * active resource with that id (a MissingResourceException when there is
 * none, or null with $throwException false). $id may be the "resource_id"
 * route parameter itself, the array of primary keys workflow routes carry.
 *
 * routed() and metadata() give what ResourceListener resolved for the
 * current request: $this->plugin('resource')->routed().
 *
 * @api
 */
final class ResourcePlugin implements PluginInterface
{
    private ?DispatchableInterface $controller = null;

    public function __construct(
        private readonly ResourceManagerInterface $resources,
    ) {}

    /**
     * The id in a resource id or a "resource_id" route parameter, or null.
     *
     * @param array<array-key, mixed> $resourceId
     *
     * @mago-expect analysis:mixed-assignment Route parameters are untyped; the type is checked here.
     */
    private static function idOf(array $resourceId): int|string|null
    {
        $id = $resourceId[AbstractResourceEntity::PRIMARY_KEY] ?? null;

        return is_int($id) || is_string($id) ? $id : null;
    }

    #[Override]
    public function getController(): ?DispatchableInterface
    {
        return $this->controller;
    }

    /**
     * The page metadata ResourceListener built for this request, or null.
     */
    public function metadata(): ?PageMetadata
    {
        $controller = $this->controller;

        return $controller instanceof InjectApplicationEventInterface
            ? ResourceParam::metadata($controller->getEvent())
            : null;
    }

    /**
     * The active resource with that id.
     *
     * @param int|string|array<array-key, mixed> $resourceId An id, or the "resource_id" route parameter.
     *
     * @throws MissingResourceException When there is none and $throwException is true.
     * @throws DbModelException
     *
     * @mago-expect lint:no-boolean-flag-parameter The 1.x signature, kept so controllers port unchanged.
     */
    public function resource(int|string|array $resourceId, bool $throwException = true): ?AbstractResourceEntity
    {
        $id       = is_array($resourceId) ? self::idOf($resourceId) : $resourceId;
        $resource = null === $id ? null : $this->resources->findActive($id);
        if (null === $resource && $throwException) {
            throw new MissingResourceException('Resource not found');
        }

        return $resource;
    }

    /**
     * The resource ResourceListener resolved for this request.
     *
     * @throws MissingResourceException When the request is not a resource route.
     */
    public function routed(): AbstractResourceEntity
    {
        $controller = $this->controller;

        return $controller instanceof InjectApplicationEventInterface
            ? ResourceParam::require($controller->getEvent())
            : throw MissingResourceException::notResolved('the controller has no MvcEvent');
    }

    #[Override]
    public function setController(DispatchableInterface $controller): void
    {
        $this->controller = $controller;
    }

    /**
     * Without an id, the resource manager; otherwise the active resource with that id.
     *
     * @param int|string|array<array-key, mixed>|null $resourceId An id, or the "resource_id" route parameter.
     *
     * @throws MissingResourceException When there is none and $throwException is true.
     * @throws DbModelException
     */
    public function __invoke(
        int|string|array|null $resourceId = null,
        bool $throwException = true,
    ): ResourceManagerInterface|AbstractResourceEntity|null {
        return null === $resourceId ? $this->resources : $this->resource($resourceId, $throwException);
    }
}

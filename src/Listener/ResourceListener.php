<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Listener;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Laminas\Mvc\ResourceParam;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\EventsCapableInterface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Override;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

/**
 * Resolves the resource of a workflow route and sets it, with its page
 * metadata, on the MvcEvent (see ResourceParam).
 *
 * Workflow routes carry the resource's primary keys as the "resource_id"
 * route default, an array; a path segment named "resource_id" is always a
 * string, so other routes pass through untouched. A resource route whose
 * resource is missing or no longer active (the route cache can outlive a
 * change in the admin) is answered with a 404, the way laminas-mvc answers
 * an unmatched route: an unpublished page never reaches its controller.
 *
 * It listens to the route event after the router (priority -100).
 *
 * @api
 */
final class ResourceListener extends AbstractListenerAggregate
{
    /**
     * The route event priority: after laminas-mvc's RouteListener (1).
     */
    public const int PRIORITY = -100;

    /**
     * The route parameter that holds the resource's primary keys.
     */
    public const string ROUTE_PARAMETER = 'resource_id';

    public function __construct(
        private readonly ResourceManagerInterface $resources,
        private readonly PageMetadataBuilder $metadata,
    ) {}

    /**
     * Turn the request into a 404, as laminas-mvc's RouteListener does for
     * a URL no route matches.
     */
    private static function notFound(MvcEvent $event): mixed
    {
        $event->setName(MvcEvent::EVENT_DISPATCH_ERROR);
        $event->setError(Application::ERROR_ROUTER_NO_MATCH);

        $target = $event->getTarget();

        return $target instanceof EventsCapableInterface
            ? $target->getEventManager()->triggerEvent($event)->last()
            : null;
    }

    /**
     * @param int $priority
     */
    #[Override]
    public function attach(EventManagerInterface $events, $priority = self::PRIORITY): void
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, $this->onRoute(...), $priority);
    }

    /**
     * @throws DbModelException
     *
     * @mago-expect analysis:mixed-assignment Route parameters are untyped; the types are checked here.
     */
    public function onRoute(MvcEvent $event): mixed
    {
        $keys = $event->getRouteMatch()?->getParam(self::ROUTE_PARAMETER);
        if (! is_array($keys) || ! array_key_exists(AbstractResourceEntity::PRIMARY_KEY, $keys)) {
            return null;
        }

        $resourceId = $keys[AbstractResourceEntity::PRIMARY_KEY];
        $resource   = is_int($resourceId) || is_string($resourceId) ? $this->resources->findActive($resourceId) : null;
        if (null === $resource) {
            return self::notFound($event);
        }

        $request = $event->getRequest();
        $event->setParam(ResourceParam::RESOURCE, $resource);
        $event->setParam(ResourceParam::METADATA, $this->metadata->build(
            $resource,
            $request instanceof HttpRequest ? $request->getUriString() : '/',
        ));

        return null;
    }
}

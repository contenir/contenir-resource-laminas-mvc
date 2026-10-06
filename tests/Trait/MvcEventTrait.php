<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Trait;

use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;

/**
 * Builds route events the way laminas-mvc's RouteListener leaves them.
 */
trait MvcEventTrait
{
    /**
     * @param array<string, mixed>|null $params Route match parameters; null for no route match.
     */
    private static function routeEvent(?array $params, string $url = 'https://www.example.com/about'): MvcEvent
    {
        $request = new Request();
        $request->setUri($url);

        $event = new MvcEvent(MvcEvent::EVENT_ROUTE);
        $event->setRequest($request);
        if (null !== $params) {
            $event->setRouteMatch(new RouteMatch($params));
        }

        return $event;
    }
}

<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Event;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\EventsCapableInterface;
use Override;

/**
 * An event target with its own event manager, standing in for the
 * Application that laminas-mvc sets as the MvcEvent target.
 */
final readonly class EventTarget implements EventsCapableInterface
{
    public function __construct(
        private EventManagerInterface $events = new EventManager(),
    ) {}

    #[Override]
    public function getEventManager(): EventManagerInterface
    {
        return $this->events;
    }
}

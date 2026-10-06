<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc;

use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Exception\MissingResourceException;
use Contenir\Resource\Core\Metadata\PageMetadata;
use Laminas\EventManager\EventInterface;

/**
 * Reads what ResourceListener set on the MvcEvent: the resolved resource
 * under AbstractResourceEntity::class, and its page metadata under
 * PageMetadata::class.
 *
 * @api
 */
final class ResourceParam
{
    public const string METADATA = PageMetadata::class;

    public const string RESOURCE = AbstractResourceEntity::class;

    /**
     * @mago-expect analysis:mixed-assignment Event parameters are untyped; the type is checked here.
     */
    public static function metadata(EventInterface $event): ?PageMetadata
    {
        $metadata = $event->getParam(self::METADATA);

        return $metadata instanceof PageMetadata ? $metadata : null;
    }

    /**
     * @throws MissingResourceException When the event has no resolved resource.
     */
    public static function require(EventInterface $event): AbstractResourceEntity
    {
        return (
            self::resource($event) ?? throw MissingResourceException::notResolved(
                'route the request through a resource workflow and load the Contenir\Resource\Laminas\Mvc module',
            )
        );
    }

    /**
     * @mago-expect analysis:mixed-assignment Event parameters are untyped; the type is checked here.
     */
    public static function resource(EventInterface $event): ?AbstractResourceEntity
    {
        $resource = $event->getParam(self::RESOURCE);

        return $resource instanceof AbstractResourceEntity ? $resource : null;
    }
}

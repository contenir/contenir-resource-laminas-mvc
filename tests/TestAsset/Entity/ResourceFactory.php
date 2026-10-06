<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity;

use Contenir\Resource\Core\Entity\ResourceEntity;
use Contenir\Resource\Core\Entity\ResourceStatus;

/**
 * Builds unsaved resource entities for unit tests.
 */
final class ResourceFactory
{
    public static function make(
        ?int $resourceId = 1,
        string $type = 'page',
        string $title = 'About',
        ResourceStatus $status = ResourceStatus::Active,
    ): ResourceEntity {
        $resource                 = new ResourceEntity();
        $resource->resourceId     = $resourceId;
        $resource->resourceTypeId = $type;
        $resource->title          = $title;
        $resource->status         = $status;
        $resource->visible        = true;

        return $resource;
    }
}

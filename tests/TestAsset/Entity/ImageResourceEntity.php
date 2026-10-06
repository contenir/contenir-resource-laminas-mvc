<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity;

use Contenir\Db\Model\Mapping\Table;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Override;

/**
 * A resource whose share image is its description column, so tests can
 * set an editor-entered image path.
 */
#[Table('resource')]
final class ImageResourceEntity extends AbstractResourceEntity
{
    #[Override]
    public function getMetaDescription(): ?string
    {
        return null;
    }

    #[Override]
    public function getMetaImage(): ?string
    {
        return $this->description;
    }
}

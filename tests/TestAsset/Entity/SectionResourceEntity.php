<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity;

use Contenir\Db\Model\Mapping\Table;
use Contenir\Resource\Core\Content\SectionAwareInterface;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Override;

/**
 * A resource with fixed section content.
 */
#[Table('resource')]
final class SectionResourceEntity extends AbstractResourceEntity implements SectionAwareInterface
{
    #[Override]
    public function getSection(): mixed
    {
        return ['heading' => 'Section heading'];
    }

    #[Override]
    public function hasSection(): bool
    {
        return true;
    }
}

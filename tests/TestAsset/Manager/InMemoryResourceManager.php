<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Manager;

use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\ResourceManagerInterface;
use LogicException;
use Override;

use function array_key_first;

/**
 * A resource manager over a fixed list of resources. Only the id lookups
 * and the helper lookups are implemented; the others are not used by the
 * adapter. Every call is recorded; findOneBy() answers with the first
 * resource whatever its criteria, which the integration suite checks against
 * a real database.
 */
final class InMemoryResourceManager implements ResourceManagerInterface
{
    /** @var list<array{string, mixed}> */
    public array $calls = [];

    /** @var array<int, AbstractResourceEntity> */
    private array $resources = [];

    public function __construct(AbstractResourceEntity ...$resources)
    {
        foreach ($resources as $resource) {
            $this->resources[$resource->getId()] = $resource;
        }
    }

    #[Override]
    public function find(int|string $resourceId): ?AbstractResourceEntity
    {
        $this->calls[] = ['find', $resourceId];

        return $this->resources[(int) $resourceId] ?? null;
    }

    #[Override]
    public function findActive(int|string $resourceId): ?AbstractResourceEntity
    {
        $this->calls[] = ['findActive', $resourceId];
        $resource      = $this->resources[(int) $resourceId] ?? null;

        return true === $resource?->isActive() ? $resource : null;
    }

    #[Override]
    public function findActiveBySlug(string $slug): ?AbstractResourceEntity
    {
        $this->calls[] = ['findActiveBySlug', $slug];

        return $this->first();
    }

    #[Override]
    public function findActiveByWorkflow(string $workflow): ?AbstractResourceEntity
    {
        $this->calls[] = ['findActiveByWorkflow', $workflow];

        return $this->first();
    }

    #[Override]
    public function findActivePageByWorkflow(string $workflow): ?AbstractResourceEntity
    {
        $this->calls[] = ['findActivePageByWorkflow', $workflow];

        return $this->first();
    }

    #[Override]
    public function findBy(array $criteria = [], array $orderBy = []): array
    {
        throw new LogicException('Not used by the adapter');
    }

    #[Override]
    public function findByType(string|array $resourceTypeId, array $criteria = [], array $orderBy = []): array
    {
        throw new LogicException('Not used by the adapter');
    }

    #[Override]
    public function findCollectionByType(string|array $resourceTypeId): array
    {
        throw new LogicException('Not used by the adapter');
    }

    #[Override]
    public function findOneBy(array $criteria, array $orderBy = []): ?AbstractResourceEntity
    {
        $this->calls[] = ['findOneBy', [$criteria, $orderBy]];

        return $this->first();
    }

    #[Override]
    public function findOneByType(
        string|array $resourceTypeId,
        array $criteria = [],
        array $orderBy = [],
    ): ?AbstractResourceEntity {
        throw new LogicException('Not used by the adapter');
    }

    private function first(): ?AbstractResourceEntity
    {
        $first = array_key_first($this->resources);

        return null === $first ? null : $this->resources[$first];
    }
}

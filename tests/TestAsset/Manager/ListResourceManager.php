<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Manager;

use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Entity\ResourceCollectionEntity;
use Contenir\Resource\Core\ResourceManagerInterface;
use Override;

use function array_values;

/**
 * A resource manager over a fixed list, answering each lookup as the site's would: by id, by
 * slug or workflow among the active resources, and lists in the order given. It records each
 * lookup it is asked.
 */
final class ListResourceManager implements ResourceManagerInterface
{
    /** @var list<string> */
    public array $calls = [];

    /** @var list<AbstractResourceEntity> */
    private array $resources;

    /**
     * @param list<ResourceCollectionEntity> $collections
     */
    public function __construct(
        array $resources = [],
        private readonly array $collections = [],
    ) {
        $this->resources = array_values($resources);
    }

    #[Override]
    public function find(int|string $resourceId): ?AbstractResourceEntity
    {
        $this->calls[] = 'find';

        return $this->first(
            static fn(AbstractResourceEntity $r): bool => (string) $r->resourceId === (string) $resourceId,
        );
    }

    #[Override]
    public function findActive(int|string $resourceId): ?AbstractResourceEntity
    {
        $this->calls[] = 'findActive';
        $id            = (string) $resourceId;

        return $this->first(
            static fn(AbstractResourceEntity $r): bool => $r->isActive() && (string) $r->resourceId === $id,
        );
    }

    #[Override]
    public function findActiveBySlug(string $slug): ?AbstractResourceEntity
    {
        $this->calls[] = 'findActiveBySlug';

        return $this->first(static fn(AbstractResourceEntity $r): bool => $r->slug === $slug && $r->isActive());
    }

    #[Override]
    public function findActiveByWorkflow(string $workflow): ?AbstractResourceEntity
    {
        $this->calls[] = 'findActiveByWorkflow';

        return $this->first(static fn(AbstractResourceEntity $r): bool => $r->workflow === $workflow && $r->isActive());
    }

    #[Override]
    public function findActivePageByWorkflow(string $workflow): ?AbstractResourceEntity
    {
        $this->calls[] = 'findActivePageByWorkflow';

        return $this->first(
            static fn(AbstractResourceEntity $r): bool => (
                'page' === $r->resourceTypeId
                && $r->workflow === $workflow
                && $r->isActive()
            ),
        );
    }

    #[Override]
    public function findBy(array $criteria = [], array $orderBy = []): array
    {
        $this->calls[] = 'findBy';

        return $this->resources;
    }

    #[Override]
    public function findByType(string|array $resourceTypeId, array $criteria = [], array $orderBy = []): array
    {
        $this->calls[] = 'findByType';

        return $this->resources;
    }

    #[Override]
    public function findCollectionByType(string|array $resourceTypeId): array
    {
        $this->calls[] = 'findCollectionByType';

        return $this->collections;
    }

    #[Override]
    public function findOneBy(array $criteria, array $orderBy = []): ?AbstractResourceEntity
    {
        $this->calls[] = 'findOneBy';

        return $this->resources[0] ?? null;
    }

    #[Override]
    public function findOneByType(
        string|array $resourceTypeId,
        array $criteria = [],
        array $orderBy = [],
    ): ?AbstractResourceEntity {
        $this->calls[] = 'findOneByType';

        return $this->resources[0] ?? null;
    }

    /**
     * @param callable(AbstractResourceEntity): bool $test
     */
    private function first(callable $test): ?AbstractResourceEntity
    {
        foreach ($this->resources as $resource) {
            if ($test($resource)) {
                return $resource;
            }
        }

        return null;
    }
}

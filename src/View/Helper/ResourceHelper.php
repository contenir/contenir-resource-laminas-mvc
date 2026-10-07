<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\View\Helper;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\ResourceManagerInterface;

use function ctype_digit;
use function is_array;
use function is_int;
use function is_string;
use function ltrim;

/**
 * View helper "resource": active resources by id, slug or workflow.
 *
 * @api
 */
final readonly class ResourceHelper
{
    public function __construct(
        private ResourceManagerInterface $resources,
    ) {}

    /**
     * @phpstan-assert-if-true int|string $id
     */
    private static function isId(mixed $id): bool
    {
        return is_int($id) ? $id > 0 : is_string($id) && ctype_digit($id) && '' !== ltrim($id, characters: '0');
    }

    /**
     * @throws DbModelException
     */
    public function findActivePageByWorkflow(string $workflow): ?AbstractResourceEntity
    {
        return $this->resources->findActivePageByWorkflow($workflow);
    }

    /**
     * @throws DbModelException
     */
    public function findBySlug(string $slug): ?AbstractResourceEntity
    {
        return $this->resources->findActiveBySlug($slug);
    }

    /**
     * @throws DbModelException
     */
    public function findByWorkflow(string $workflow): ?AbstractResourceEntity
    {
        return $this->resources->findActiveByWorkflow($workflow);
    }

    /**
     * Without an id, the helper itself; otherwise the active resource with
     * that id, or null. A list of ids, as section link fields store them,
     * yields the first id that resolves to an active resource.
     *
     * @param int|string|array<array-key, mixed>|null $resourceId
     *
     * @throws DbModelException
     *
     * @mago-expect analysis:mixed-assignment Link field values are untyped; each is checked before use.
     */
    public function __invoke(int|string|array|null $resourceId = null): self|AbstractResourceEntity|null
    {
        if (null === $resourceId) {
            return $this;
        }

        if (! is_array($resourceId)) {
            return $this->resources->findActive($resourceId);
        }

        foreach ($resourceId as $id) {
            if (! self::isId($id)) {
                continue;
            }

            $resource = $this->resources->findActive($id);
            if (null !== $resource) {
                return $resource;
            }
        }

        return null;
    }
}

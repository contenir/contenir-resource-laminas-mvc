<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\View\Helper;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Laminas\Mvc\Exception\InvalidResourceIdException;

use function is_int;
use function is_iterable;
use function is_string;
use function preg_match;

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
     * The id in an argument or list entry: an int as it is, a string of
     * digits as an int, or null for a blank string.
     *
     * @throws InvalidResourceIdException For anything else.
     */
    private static function idOf(mixed $value): ?int
    {
        return match (true) {
            is_int($value) => $value,
            '' === $value => null,
            is_string($value) && 1 === preg_match('/^[0-9]+$/D', $value) => (int) $value,
            default => throw InvalidResourceIdException::forValue($value),
        };
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
     * Without an id, the helper itself. With an id, the active resource with
     * that id, or null. With a list of ids, as CMS sections store them, the
     * active resource with the lowest of those ids, or null, as 1.x's
     * "resource_id IN (...)" lookup found it. Blank strings find nothing and
     * are skipped in a list; an empty list finds nothing without a query.
     *
     * @param int|string|iterable<mixed>|null $resourceId An id (an int or a string of digits), or a list of them.
     *
     * @throws InvalidResourceIdException When an id is not an int, a blank string or a string of digits.
     * @throws DbModelException
     *
     * @mago-expect analysis:mixed-assignment List entries are untyped section data; idOf() checks each one.
     */
    public function __invoke(int|string|iterable|null $resourceId = null): self|AbstractResourceEntity|null
    {
        if (null === $resourceId) {
            return $this;
        }

        if (! is_iterable($resourceId)) {
            $id = self::idOf($resourceId);

            return null === $id ? null : $this->resources->findActive($id);
        }

        $ids = [];
        foreach ($resourceId as $value) {
            $id = self::idOf($value);
            if (null !== $id) {
                $ids[] = $id;
            }
        }

        return (
            [] === $ids
                ? null
                : $this->resources->findOneBy(
                    ['resourceId' => $ids, 'status' => ResourceStatus::Active],
                    ['resourceId' => 'ASC'],
                )
        );
    }
}

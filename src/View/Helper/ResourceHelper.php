<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\View\Helper;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\ResourceManagerInterface;

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
     * that id, or null.
     *
     * @throws DbModelException
     */
    public function __invoke(int|string|null $resourceId = null): self|AbstractResourceEntity|null
    {
        return null === $resourceId ? $this : $this->resources->findActive($resourceId);
    }
}

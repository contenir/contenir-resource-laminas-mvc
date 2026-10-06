<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Workflow;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Mvc\Workflow\Resource\ResourceAdapterInterface;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Repository\ResourceRepository;
use Override;

use function array_map;

/**
 * The workflow-laminas-mvc resource adapter, for
 * "workflow_manager.strategy.repository": the repository's page tree
 * (active top-level pages and their active descendants), as workflow
 * resources. It replaces 1.x BaseResourceRepository::getWorkflowResources().
 *
 * @api
 */
final readonly class ResourceTreeAdapter implements ResourceAdapterInterface
{
    public function __construct(
        private ResourceRepository $resources,
    ) {}

    /**
     * @return list<WorkflowResource>
     *
     * @throws DbModelException
     */
    #[Override]
    public function getWorkflowResources(): array
    {
        return array_map(
            static fn(AbstractResourceEntity $resource): WorkflowResource => new WorkflowResource($resource),
            $this->resources->findPageTree(),
        );
    }
}

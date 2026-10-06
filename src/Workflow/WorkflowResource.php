<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Workflow;

use Contenir\Metadata\MetadataInterface;
use Contenir\Mvc\Workflow\Resource\ResourceInterface;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Exception\MissingResourceException;
use DateTimeInterface;
use Override;

use function array_map;
use function trim;

/**
 * Presents a resource entity, and its attached children, as a
 * workflow-laminas-mvc ResourceInterface. The entity stays framework
 * neutral; workflows reach it through getEntity().
 *
 * workflow-laminas-mvc reads the 1.x column names as properties; they are
 * answered from the entity:
 *
 * | Property           | Value                                          |
 * | ------------------ | ---------------------------------------------- |
 * | `workflow`         | getWorkflowName(): the column, or "page"       |
 * | `title_short`      | getNavigationLabel(): short title, or title    |
 * | `title`            | the title, or null when blank                  |
 * | `visible`          | isVisible()                                    |
 * | `children`         | the attached children, as WorkflowResources    |
 * | `resource_type_id` | getType()                                      |
 * | `resource_id`      | the resource id                                |
 *
 * The metadata getters delegate to the entity, so the navigation lastmod is
 * the entity's modified date.
 *
 * @property-read string|null $workflow
 * @property-read string|null $title_short
 * @property-read string|null $title
 * @property-read bool|null $visible
 * @property-read list<WorkflowResource>|null $children
 * @property-read string|null $resource_type_id
 * @property-read int|null $resource_id
 *
 * @api
 *
 * @mago-expect lint:too-many-methods The two interfaces it implements, delegated to the entity.
 */
final readonly class WorkflowResource implements ResourceInterface, MetadataInterface
{
    public function __construct(
        private AbstractResourceEntity $entity,
    ) {}

    /**
     * @return list<WorkflowResource>
     */
    public function getChildren(): array
    {
        return array_map(
            static fn(AbstractResourceEntity $child): self => new self($child),
            $this->entity->getChildren(),
        );
    }

    public function getEntity(): AbstractResourceEntity
    {
        return $this->entity;
    }

    #[Override]
    public function getMetaDescription(): ?string
    {
        return $this->entity->getMetaDescription();
    }

    #[Override]
    public function getMetaImage(): ?string
    {
        return $this->entity->getMetaImage();
    }

    #[Override]
    public function getMetaModified(): ?DateTimeInterface
    {
        return $this->entity->getMetaModified();
    }

    #[Override]
    public function getMetaPublish(): ?DateTimeInterface
    {
        return $this->entity->getMetaPublish();
    }

    #[Override]
    public function getMetaTitle(): ?string
    {
        return $this->entity->getMetaTitle();
    }

    /**
     * @return array{resourceId: int}
     *
     * @throws MissingResourceException When the resource has not been saved.
     */
    #[Override]
    public function getPrimaryKeys(): array
    {
        return $this->entity->getPrimaryKeys();
    }

    #[Override]
    public function getSlug(): string
    {
        return $this->entity->getSlug();
    }

    private function title(): ?string
    {
        $title = $this->entity->title;

        return null === $title || '' === trim($title) ? null : $title;
    }

    /**
     * One of the properties listed above, or null.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'workflow'         => $this->entity->getWorkflowName(),
            'title_short'      => $this->entity->getNavigationLabel(),
            'title'            => $this->title(),
            'visible'          => $this->entity->isVisible(),
            'children'         => $this->getChildren(),
            'resource_type_id' => $this->entity->getType(),
            'resource_id'      => $this->entity->resourceId,
            default            => null,
        };
    }

    public function __isset(string $name): bool
    {
        return null !== $this->__get($name);
    }
}

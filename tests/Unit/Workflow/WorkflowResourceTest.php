<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Unit\Workflow;

use Contenir\Mvc\Workflow\Resource\ResourceProperty;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Laminas\Mvc\Workflow\WorkflowResource;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class WorkflowResourceTest extends TestCase
{
    /**
     * @return array<string, array{string|null}>
     */
    public static function blankTitleProvider(): array
    {
        return [
            'null'       => [null],
            'empty'      => [''],
            'whitespace' => [' '],
        ];
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function propertyProvider(): array
    {
        return [
            'workflow'         => ['workflow', 'news'],
            'title_short'      => ['title_short', 'Who'],
            'title'            => ['title', 'About'],
            'visible'          => ['visible', true],
            'resource_type_id' => ['resource_type_id', 'page'],
            'resource_id'      => ['resource_id', 7],
            'unknown'          => ['description', null],
        ];
    }

    private static function resource(): WorkflowResource
    {
        $entity              = ResourceFactory::make(resourceId: 7);
        $entity->workflow    = 'news';
        $entity->titleShort  = 'Who';
        $entity->slug        = 'about';
        $entity->metaTitle   = 'About us';
        $entity->description = 'Fish';
        $entity->created     = new DateTimeImmutable('2024-01-01');
        $entity->updated     = new DateTimeImmutable('2024-02-01');

        return new WorkflowResource($entity);
    }

    #[Test]
    #[DataProvider('blankTitleProvider')]
    public function blankTitlesAreNull(?string $title): void
    {
        $entity        = ResourceFactory::make();
        $entity->title = $title;

        static::assertNull((new WorkflowResource($entity))->title);
    }

    #[Test]
    public function childrenAreWrapped(): void
    {
        $parent = ResourceFactory::make();
        $child  = ResourceFactory::make(
            resourceId: 2,
            title: 'Team',
        );
        $parent->setChildren([$child]);

        $resource = new WorkflowResource($parent);

        static::assertEquals(
            [[new WorkflowResource($child)], [new WorkflowResource($child)]],
            [ResourceProperty::children($resource), $resource->getChildren()],
        );
    }

    #[Test]
    #[DataProvider('propertyProvider')]
    public function itAnswersTheWorkflowProperties(string $name, mixed $expected): void
    {
        static::assertSame(
            [$expected, null !== $expected],
            [ResourceProperty::value(self::resource(), $name), self::resource()->__isset($name)],
        );
    }

    #[Test]
    public function itDelegatesToTheEntity(): void
    {
        $resource = self::resource();

        static::assertEquals(
            [
                ['resourceId' => 7],
                'about',
                'About us',
                'Fish',
                null,
                new DateTimeImmutable('2024-02-01'),
                new DateTimeImmutable('2024-01-01'),
                7,
            ],
            [
                $resource->getPrimaryKeys(),
                $resource->getSlug(),
                $resource->getMetaTitle(),
                $resource->getMetaDescription(),
                $resource->getMetaImage(),
                $resource->getMetaModified(),
                $resource->getMetaPublish(),
                $resource->getEntity()->resourceId,
            ],
        );
    }

    #[Test]
    public function theShortTitleFallsBackToTheTitle(): void
    {
        static::assertSame('About', (new WorkflowResource(ResourceFactory::make()))->title_short);
    }

    #[Test]
    public function theWorkflowDefaultsToPage(): void
    {
        static::assertSame('page', (new WorkflowResource(ResourceFactory::make()))->workflow);
    }

    #[Test]
    public function withoutChildrenTheListIsEmpty(): void
    {
        static::assertSame([], (new WorkflowResource(ResourceFactory::make()))->children);
    }
}

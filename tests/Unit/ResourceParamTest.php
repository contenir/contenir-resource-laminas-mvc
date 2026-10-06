<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Unit;

use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Exception\MissingResourceException;
use Contenir\Resource\Core\Metadata\PageMetadata;
use Contenir\Resource\Laminas\Mvc\ResourceParam;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity\ResourceFactory;
use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ResourceParamTest extends TestCase
{
    #[Test]
    public function itIgnoresValuesOfTheWrongType(): void
    {
        $event = new MvcEvent();
        $event->setParam(AbstractResourceEntity::class, 'about');
        $event->setParam(PageMetadata::class, 'meta');

        static::assertSame([null, null], [ResourceParam::resource($event), ResourceParam::metadata($event)]);
    }

    #[Test]
    public function itReadsTheResourceAndMetadata(): void
    {
        $about    = ResourceFactory::make();
        $metadata = new PageMetadata('https://s.test/');
        $event    = new MvcEvent();
        $event->setParam(AbstractResourceEntity::class, $about);
        $event->setParam(PageMetadata::class, $metadata);

        static::assertSame(
            [$about, $about, $metadata],
            [ResourceParam::resource($event), ResourceParam::require($event), ResourceParam::metadata($event)],
        );
    }

    #[Test]
    public function requireFailsWithoutAResource(): void
    {
        $this->expectException(MissingResourceException::class);
        $this->expectExceptionMessage(
            'No resource was resolved for this request; route the request through a resource workflow and load the '
                . 'Contenir\Resource\Laminas\Mvc module',
        );

        ResourceParam::require(new MvcEvent());
    }
}

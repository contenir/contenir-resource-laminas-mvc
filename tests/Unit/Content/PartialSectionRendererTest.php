<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Unit\Content;

use Contenir\Resource\Laminas\Mvc\Content\PartialSectionRenderer;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Entity\SectionResourceEntity;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\View\RecordingRenderer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class PartialSectionRendererTest extends TestCase
{
    #[Test]
    public function itRendersTheConfiguredTemplate(): void
    {
        static::assertSame(
            '<div>site/section</div>',
            (new PartialSectionRenderer(new RecordingRenderer(), 'site/section'))->render(new SectionResourceEntity()),
        );
    }

    #[Test]
    public function itRendersTheLegacyPartialByDefault(): void
    {
        $renderer = new RecordingRenderer();
        $resource = new SectionResourceEntity();

        $html = (new PartialSectionRenderer($renderer))->render($resource);

        static::assertSame(
            [
                '<div>application/component/_section</div>',
                [[
                    'application/component/_section',
                    ['section' => ['heading' => 'Section heading'], 'resource' => $resource],
                ]],
            ],
            [$html, $renderer->calls],
        );
    }
}

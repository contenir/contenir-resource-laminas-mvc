<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\TestAsset\View;

use Laminas\View\Renderer\RendererInterface;
use Laminas\View\Resolver\ResolverInterface;
use LogicException;
use Override;

use function is_string;

/**
 * A renderer that records each render() call and renders the template
 * name in a div.
 */
final class RecordingRenderer implements RendererInterface
{
    /** @var list<array{mixed, mixed}> */
    public array $calls = [];

    #[Override]
    public function getEngine(): self
    {
        return $this;
    }

    #[Override]
    public function render($nameOrModel, $values = null): string
    {
        $this->calls[] = [$nameOrModel, $values];

        return '<div>' . (is_string($nameOrModel) ? $nameOrModel : '') . '</div>';
    }

    #[Override]
    public function setResolver(ResolverInterface $resolver): never
    {
        throw new LogicException('Not used by the adapter');
    }
}

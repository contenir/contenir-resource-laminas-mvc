<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Router;

use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\RouteStackInterface;
use Laminas\Stdlib\RequestInterface;
use LogicException;
use Override;

use function array_key_exists;
use function is_string;

/**
 * A route stack that assembles fixed paths by route name, recording each
 * assemble() call, and throws laminas-router's exception for other names.
 */
final class RecordingRouter implements RouteStackInterface
{
    /** @var list<array{array<array-key, mixed>, array<array-key, mixed>}> */
    public array $calls = [];

    /**
     * @param array<string, string> $paths
     */
    public function __construct(
        private readonly array $paths = [],
    ) {}

    /**
     * @param iterable<array-key, mixed>|array<array-key, mixed> $options
     */
    #[Override]
    public static function factory($options = []): self
    {
        return new self();
    }

    #[Override]
    public function addRoute($name, $route, $priority = null): self
    {
        throw new LogicException('Not used by the adapter');
    }

    #[Override]
    public function addRoutes($routes): self
    {
        throw new LogicException('Not used by the adapter');
    }

    /**
     * @param array<array-key, mixed> $params
     * @param array<array-key, mixed> $options
     */
    #[Override]
    public function assemble(array $params = [], array $options = []): string
    {
        $this->calls[] = [$params, $options];
        $name          = $options['name'] ?? null;
        if (is_string($name) && array_key_exists($name, $this->paths)) {
            return $this->paths[$name];
        }

        throw new RuntimeException('Route not found');
    }

    #[Override]
    public function match(RequestInterface $request): never
    {
        throw new LogicException('Not used by the adapter');
    }

    #[Override]
    public function removeRoute($name): self
    {
        throw new LogicException('Not used by the adapter');
    }

    #[Override]
    public function setRoutes($routes): self
    {
        throw new LogicException('Not used by the adapter');
    }
}

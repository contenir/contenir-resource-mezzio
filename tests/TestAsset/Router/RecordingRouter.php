<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\TestAsset\Router;

use LogicException;
use Mezzio\Router\Exception\RuntimeException;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Generates URIs from a fixed route name => path map; an unknown name
 * throws like a real router does.
 */
final readonly class RecordingRouter implements RouterInterface
{
    /**
     * @param array<string, string> $paths
     */
    public function __construct(
        private array $paths = [],
    ) {}

    #[Override]
    public function addRoute(Route $route): void
    {
        throw new LogicException('Not used');
    }

    #[Override]
    public function generateUri(string $name, array $substitutions = [], array $options = []): string
    {
        return $this->paths[$name] ?? throw new RuntimeException("No route {$name}");
    }

    #[Override]
    public function match(ServerRequestInterface $request): RouteResult
    {
        throw new LogicException('Not used');
    }
}

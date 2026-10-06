<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\TestAsset\Handler;

use Laminas\Diactoros\Response\TextResponse;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Answers with a fixed status and body and keeps the request it handled.
 */
final class RecordingHandler implements RequestHandlerInterface
{
    public ?ServerRequestInterface $request = null;

    public function __construct(
        private readonly int $status = 200,
        private readonly string $body = 'handled',
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        return new TextResponse($this->body, $this->status);
    }
}

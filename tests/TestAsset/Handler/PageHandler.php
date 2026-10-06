<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\TestAsset\Handler;

use Contenir\Resource\Core\Metadata\MetaTagRenderer;
use Contenir\Resource\Mezzio\ResourceAttribute;
use Laminas\Diactoros\Response\HtmlResponse;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A page handler as a site would write one: the resolved resource's title
 * and its rendered head metadata.
 */
final readonly class PageHandler implements RequestHandlerInterface
{
    public function __construct(
        private MetaTagRenderer $renderer = new MetaTagRenderer(),
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $resource = ResourceAttribute::require($request);
        $metadata = ResourceAttribute::metadata($request);

        return new HtmlResponse(
            "resource {$resource->getId()}\n" . (null === $metadata ? '' : $this->renderer->render($metadata)),
        );
    }
}

<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Middleware;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Mezzio\ResourceAttribute;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

/**
 * Resolves the resource of a workflow route and attaches it, with its page
 * metadata, to the request (see ResourceAttribute).
 *
 * Workflow routes carry the resource's primary keys as the "id" route
 * default, an array; a path placeholder named "id" is always a string, so
 * other routes pass through untouched. A resource route whose resource is
 * missing or no longer active (the route cache can outlive a change in the
 * admin) is answered by the not-found handler: an unpublished page is never
 * handed to the page's handler.
 *
 * Pipe it after RouteMiddleware and before DispatchMiddleware.
 *
 * @api
 */
final readonly class ResourceMiddleware implements MiddlewareInterface
{
    /**
     * The route parameter that holds the resource's primary keys.
     */
    public const string ROUTE_PARAMETER = 'id';

    public function __construct(
        private ResourceManagerInterface $resources,
        private PageMetadataBuilder $metadata,
        private RequestHandlerInterface $notFoundHandler,
    ) {}

    /**
     * @throws DbModelException
     *
     * @mago-expect analysis:mixed-assignment Route parameters are untyped; the types are checked here.
     */
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $keys = $request->getAttribute(self::ROUTE_PARAMETER);
        if (! is_array($keys) || ! array_key_exists(AbstractResourceEntity::PRIMARY_KEY, $keys)) {
            return $handler->handle($request);
        }

        $resourceId = $keys[AbstractResourceEntity::PRIMARY_KEY];
        $resource   = is_int($resourceId) || is_string($resourceId) ? $this->resources->findActive($resourceId) : null;
        if (null === $resource) {
            return $this->notFoundHandler->handle($request);
        }

        return $handler->handle(
            $request->withAttribute(ResourceAttribute::RESOURCE, $resource)
                ->withAttribute(ResourceAttribute::METADATA, $this->metadata->build(
                    $resource,
                    (string) $request->getUri(),
                )),
        );
    }
}

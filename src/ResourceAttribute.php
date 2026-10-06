<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio;

use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Exception\MissingResourceException;
use Contenir\Resource\Core\Metadata\PageMetadata;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Reads what ResourceMiddleware attached to the request: the resolved
 * resource under AbstractResourceEntity::class, and its page metadata under
 * PageMetadata::class.
 *
 * @api
 */
final class ResourceAttribute
{
    public const string METADATA = PageMetadata::class;

    public const string RESOURCE = AbstractResourceEntity::class;

    /**
     * @mago-expect analysis:mixed-assignment Request attributes are untyped; the type is checked here.
     */
    public static function metadata(ServerRequestInterface $request): ?PageMetadata
    {
        $metadata = $request->getAttribute(self::METADATA);

        return $metadata instanceof PageMetadata ? $metadata : null;
    }

    /**
     * @throws MissingResourceException When the request has no resolved resource.
     */
    public static function require(ServerRequestInterface $request): AbstractResourceEntity
    {
        return (
            self::resource($request) ?? throw MissingResourceException::notResolved(
                'route the request through a resource workflow and pipe ResourceMiddleware after RouteMiddleware',
            )
        );
    }

    /**
     * @mago-expect analysis:mixed-assignment Request attributes are untyped; the type is checked here.
     */
    public static function resource(ServerRequestInterface $request): ?AbstractResourceEntity
    {
        $resource = $request->getAttribute(self::RESOURCE);

        return $resource instanceof AbstractResourceEntity ? $resource : null;
    }
}

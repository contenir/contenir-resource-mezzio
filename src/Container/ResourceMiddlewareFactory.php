<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Container;

use Contenir\Resource\Core\Container\ConfigReader;
use Contenir\Resource\Core\Container\ServiceLocator;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Mezzio\Middleware\ResourceMiddleware;
use Mezzio\Handler\NotFoundHandler;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Builds ResourceMiddleware with the "contenir_resource.not_found_handler"
 * service (default Mezzio\Handler\NotFoundHandler).
 *
 * @api
 */
final class ResourceMiddlewareFactory
{
    /**
     * @throws ConfigurationException When a service has the wrong type.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ResourceMiddleware
    {
        return new ResourceMiddleware(
            ServiceLocator::get($container, ResourceManagerInterface::class, ResourceManagerInterface::class),
            ServiceLocator::get($container, PageMetadataBuilder::class, PageMetadataBuilder::class),
            ServiceLocator::get(
                $container,
                ConfigReader::fromContainer($container)->string('not_found_handler', NotFoundHandler::class),
                RequestHandlerInterface::class,
            ),
        );
    }
}

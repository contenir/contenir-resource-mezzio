<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Container;

use Contenir\Resource\Core\Container\ServiceLocator;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Mezzio\Url\ResourceUrlGenerator;
use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @api
 */
final class ResourceUrlGeneratorFactory
{
    /**
     * @throws ConfigurationException When a service has the wrong type.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ResourceUrlGenerator
    {
        return new ResourceUrlGenerator(
            ServiceLocator::get($container, RouterInterface::class, RouterInterface::class),
            ServiceLocator::get($container, ResourceManagerInterface::class, ResourceManagerInterface::class),
        );
    }
}

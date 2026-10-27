<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Container;

use Contenir\Resource\Core\Container\ServiceLocator;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Mezzio\Preview\WorkflowPageResolver;
use Contenir\Workflow\Workflow\WorkflowPluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @api
 */
final class WorkflowPageResolverFactory
{
    /**
     * @throws ConfigurationException When the workflow manager or resource manager service has the wrong type.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): WorkflowPageResolver
    {
        return new WorkflowPageResolver(
            ServiceLocator::get($container, WorkflowPluginManager::class, WorkflowPluginManager::class),
            ServiceLocator::get($container, ResourceManagerInterface::class, ResourceManagerInterface::class),
        );
    }
}

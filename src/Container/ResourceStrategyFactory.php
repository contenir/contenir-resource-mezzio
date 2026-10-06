<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Container;

use Contenir\Resource\Core\Container\ConfigReader;
use Contenir\Resource\Core\Container\ServiceLocator;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Mezzio\Workflow\ResourceStrategy;
use Contenir\Resource\Mezzio\Workflow\ResourceTreeAdapter;
use Contenir\Workflow\Repository\ResourceAdapterInterface;
use Contenir\Workflow\Workflow\WorkflowPluginManager;
use Laminas\Cache\Storage\StorageInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Builds the ResourceStrategy from the "workflow_manager" config, as
 * workflow-mezzio's own factory does: "repository" (default
 * ResourceTreeAdapter), "cache" (default "FilesystemCache") and
 * "cache_key" (default "WorkflowResourceCache").
 *
 * @api
 */
final class ResourceStrategyFactory
{
    public const string SECTION = 'workflow_manager';

    /**
     * @throws ConfigurationException When a value or service is not usable.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ResourceStrategy
    {
        $config = ConfigReader::fromContainer($container, self::SECTION);

        return new ResourceStrategy(
            ServiceLocator::get(
                $container,
                $config->string('repository', ResourceTreeAdapter::class),
                ResourceAdapterInterface::class,
            ),
            ServiceLocator::get($container, WorkflowPluginManager::class, WorkflowPluginManager::class),
            ServiceLocator::get($container, $config->string('cache', 'FilesystemCache'), StorageInterface::class),
            ['cache_key' => $config->string('cache_key', 'WorkflowResourceCache')],
        );
    }
}

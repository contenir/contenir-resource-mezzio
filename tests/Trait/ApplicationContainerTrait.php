<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Trait;

use Contenir\Db\Model\ConfigProvider as DbModelConfigProvider;
use Contenir\Resource\Core\ConfigProvider as ResourceConfigProvider;
use Contenir\Resource\Mezzio\ConfigProvider;
use Contenir\Resource\Mezzio\Tests\TestAsset\Handler\PageHandler;
use Contenir\Resource\Mezzio\Tests\TestAsset\Workflow\PageHandlerWorkflow;
use Contenir\Resource\Mezzio\Workflow\ResourceStrategy;
use Contenir\Workflow\ConfigProvider as WorkflowConfigProvider;
use Contenir\Workflow\Factory\WorkflowApplicationDelegatorFactory;
use Contenir\Workflow\Workflow\WorkflowFactory;
use Contenir\Workflow\Workflow\WorkflowPluginManager;
use Laminas\Cache\Storage\Adapter\Memory;
use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Application;
use Mezzio\ConfigProvider as MezzioConfigProvider;
use Mezzio\Router\ConfigProvider as RouterConfigProvider;
use Mezzio\Router\FastRouteRouter\ConfigProvider as FastRouteConfigProvider;
use PhpDb\Adapter\AdapterInterface;

/**
 * An isolated application container per test: the Mezzio, router,
 * contenir-db-model, workflow-mezzio, contenir-resource and adapter config
 * providers, the test's SQLite adapter, an in-memory route cache and a
 * "handler" workflow routing to PageHandler. Needs SqliteDatabaseTrait.
 */
trait ApplicationContainerTrait
{
    private ServiceManager $container;

    /**
     * @param array<string, mixed> $resourceConfig
     */
    private function setUpApplicationContainer(array $resourceConfig = []): void
    {
        $config = (new ConfigAggregator([
            MezzioConfigProvider::class,
            RouterConfigProvider::class,
            FastRouteConfigProvider::class,
            DbModelConfigProvider::class,
            WorkflowConfigProvider::class,
            ResourceConfigProvider::class,
            ConfigProvider::class,
            new ArrayProvider([
                'contenir_resource' => $resourceConfig,
                'workflow_manager'  => ['strategy' => ResourceStrategy::class, 'cache' => 'RouteCache'],
                'dependencies'      => [
                    'services'   => [AdapterInterface::class => $this->adapter, 'RouteCache' => new Memory()],
                    'invokables' => [PageHandler::class => PageHandler::class],
                    'delegators' => [Application::class => [WorkflowApplicationDelegatorFactory::class]],
                ],
            ]),
        ]))->getMergedConfig();

        $dependencies                                            = $config['dependencies'];
        $dependencies['services']['config']                      = $config;
        $dependencies['factories'][WorkflowPluginManager::class] = static fn(ServiceManager $container): WorkflowPluginManager => new WorkflowPluginManager($container, [
            'aliases'   => ['handler' => PageHandlerWorkflow::class],
            'factories' => [PageHandlerWorkflow::class => WorkflowFactory::class],
        ]);

        $this->container = new ServiceManager($dependencies);
    }
}

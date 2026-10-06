<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio;

use Contenir\Resource\Mezzio\Container\ResourceMiddlewareFactory;
use Contenir\Resource\Mezzio\Container\ResourceStrategyFactory;
use Contenir\Resource\Mezzio\Container\ResourceTreeAdapterFactory;
use Contenir\Resource\Mezzio\Container\ResourceUrlGeneratorFactory;
use Contenir\Resource\Mezzio\Container\TemplateSectionRendererFactory;
use Contenir\Resource\Mezzio\Container\ViewHelperFactory;
use Contenir\Resource\Mezzio\Content\TemplateSectionRenderer;
use Contenir\Resource\Mezzio\Middleware\ResourceMiddleware;
use Contenir\Resource\Mezzio\Url\ResourceUrlGenerator;
use Contenir\Resource\Mezzio\View\Helper\ResourceContentHelper;
use Contenir\Resource\Mezzio\View\Helper\ResourceHelper;
use Contenir\Resource\Mezzio\View\Helper\ResourceMetaHelper;
use Contenir\Resource\Mezzio\View\Helper\ResourceUrlHelper;
use Contenir\Resource\Mezzio\Workflow\ResourceStrategy;
use Contenir\Resource\Mezzio\Workflow\ResourceTreeAdapter;

/**
 * Registers the Mezzio services and, for mezzio-laminasviewrenderer, the
 * view helpers. The resource services themselves (repositories, manager,
 * metadata builder) come from contenir/contenir-resource's ConfigProvider,
 * which must be registered too.
 *
 * It contributes no "contenir_resource" or "workflow_manager" config: every
 * setting has a default in code, and routing is switched on by the
 * application's own workflow_manager config.
 *
 * @api
 */
final class ConfigProvider
{
    /**
     * @return array{factories: array<class-string, class-string>}
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                ResourceMiddleware::class      => ResourceMiddlewareFactory::class,
                ResourceStrategy::class        => ResourceStrategyFactory::class,
                ResourceTreeAdapter::class     => ResourceTreeAdapterFactory::class,
                ResourceUrlGenerator::class    => ResourceUrlGeneratorFactory::class,
                TemplateSectionRenderer::class => TemplateSectionRendererFactory::class,
            ],
        ];
    }

    /**
     * @return array{aliases: array<string, string>, factories: array<string, class-string>}
     */
    public function getViewHelpers(): array
    {
        return [
            'aliases'   => [
                'resource'        => ResourceHelper::class,
                'Resource'        => ResourceHelper::class,
                'resourceContent' => ResourceContentHelper::class,
                'ResourceContent' => ResourceContentHelper::class,
                'resourceMeta'    => ResourceMetaHelper::class,
                'ResourceMeta'    => ResourceMetaHelper::class,
                'resourceUrl'     => ResourceUrlHelper::class,
                'ResourceUrl'     => ResourceUrlHelper::class,
            ],
            'factories' => [
                ResourceHelper::class        => ViewHelperFactory::class,
                ResourceContentHelper::class => ViewHelperFactory::class,
                ResourceMetaHelper::class    => ViewHelperFactory::class,
                ResourceUrlHelper::class     => ViewHelperFactory::class,
            ],
        ];
    }

    /**
     * @return array{dependencies: array{factories: array<class-string, class-string>}, view_helpers: array{aliases: array<string, string>, factories: array<string, class-string>}}
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'view_helpers' => $this->getViewHelpers(),
        ];
    }
}

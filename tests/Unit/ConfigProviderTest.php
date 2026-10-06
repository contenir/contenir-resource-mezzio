<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Unit;

use Contenir\Resource\Mezzio\ConfigProvider;
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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function contributesDependenciesAndViewHelpersOnly(): void
    {
        $provider = new ConfigProvider();

        static::assertSame(
            ['dependencies' => $provider->getDependencies(), 'view_helpers' => $provider->getViewHelpers()],
            $provider(),
        );
    }

    #[Test]
    public function registersTheMezzioServices(): void
    {
        static::assertSame(
            ['factories' => [
                ResourceMiddleware::class      => ResourceMiddlewareFactory::class,
                ResourceStrategy::class        => ResourceStrategyFactory::class,
                ResourceTreeAdapter::class     => ResourceTreeAdapterFactory::class,
                ResourceUrlGenerator::class    => ResourceUrlGeneratorFactory::class,
                TemplateSectionRenderer::class => TemplateSectionRendererFactory::class,
            ]],
            (new ConfigProvider())->getDependencies(),
        );
    }

    #[Test]
    public function registersTheViewHelpersUnderTheirLaminasMvcNames(): void
    {
        static::assertSame(
            [
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
            ],
            (new ConfigProvider())->getViewHelpers(),
        );
    }
}

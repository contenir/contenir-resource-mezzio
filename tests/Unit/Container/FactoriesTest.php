<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Unit\Container;

use Contenir\Db\Model\EntityManager;
use Contenir\Resource\Core\Content\ResourceSummary;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Core\Repository\ResourceRepository;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Mezzio\Container\ResourceMiddlewareFactory;
use Contenir\Resource\Mezzio\Container\ResourceStrategyFactory;
use Contenir\Resource\Mezzio\Container\ResourceTreeAdapterFactory;
use Contenir\Resource\Mezzio\Container\ResourceUrlGeneratorFactory;
use Contenir\Resource\Mezzio\Container\TemplateSectionRendererFactory;
use Contenir\Resource\Mezzio\Tests\TestAsset\Container\ArrayContainer;
use Contenir\Resource\Mezzio\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Mezzio\Tests\TestAsset\Entity\SectionResourceEntity;
use Contenir\Resource\Mezzio\Tests\TestAsset\Handler\RecordingHandler;
use Contenir\Resource\Mezzio\Tests\TestAsset\Manager\InMemoryResourceManager;
use Contenir\Resource\Mezzio\Tests\TestAsset\Router\RecordingRouter;
use Contenir\Resource\Mezzio\Tests\TestAsset\Template\RecordingRenderer;
use Contenir\Resource\Mezzio\Tests\TestAsset\Workflow\InMemoryResourceAdapter;
use Contenir\Resource\Mezzio\Workflow\ResourceTreeAdapter;
use Contenir\Resource\Mezzio\Workflow\WorkflowResource;
use Contenir\Workflow\Workflow\WorkflowPluginManager;
use Laminas\Cache\Storage\Adapter\Memory;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[Group('unit')]
final class FactoriesTest extends TestCase
{
    /**
     * @param array<string, mixed> $services
     */
    private static function middlewareContainer(array $services): ArrayContainer
    {
        return new ArrayContainer([
            ResourceManagerInterface::class => new InMemoryResourceManager(ResourceFactory::make()),
            PageMetadataBuilder::class      => new PageMetadataBuilder(),
            ...$services,
        ]);
    }

    #[Test]
    public function theMiddlewareRejectsANotFoundHandlerOfTheWrongType(): void
    {
        $this->expectException(ConfigurationException::class);

        (new ResourceMiddlewareFactory())(self::middlewareContainer([NotFoundHandler::class => new stdClass()]));
    }

    #[Test]
    public function theMiddlewareUsesMezziosNotFoundHandlerByDefault(): void
    {
        $notFound   = new RecordingHandler(status: 404);
        $middleware = (new ResourceMiddlewareFactory())(self::middlewareContainer([
            NotFoundHandler::class => $notFound,
        ]));

        $response = $middleware->process(
            (new ServerRequest())->withAttribute('id', ['resourceId' => 99]),
            new RecordingHandler(),
        );

        static::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function theMiddlewareUsesTheConfiguredNotFoundHandler(): void
    {
        $middleware = (new ResourceMiddlewareFactory())(self::middlewareContainer([
            'config'  => ['contenir_resource' => ['not_found_handler' => 'missing']],
            'missing' => new RecordingHandler(status: 410),
        ]));

        $response = $middleware->process(
            (new ServerRequest())->withAttribute('id', ['resourceId' => 99]),
            new RecordingHandler(),
        );

        static::assertSame(410, $response->getStatusCode());
    }

    #[Test]
    public function theSectionRendererNeedsATemplate(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Config "contenir_resource.section_template" must be a template name, got null');

        (new TemplateSectionRendererFactory())(new ArrayContainer([
            TemplateRendererInterface::class => new RecordingRenderer(),
        ]));
    }

    #[Test]
    public function theSectionRendererSummarisesThroughResourceSummary(): void
    {
        $renderer = (new TemplateSectionRendererFactory())(new ArrayContainer([
            'config'                         => ['contenir_resource' => ['section_template' => 'app::section']],
            TemplateRendererInterface::class => new RecordingRenderer(),
        ]));

        static::assertSame('app::section', (new ResourceSummary($renderer))->summarise(new SectionResourceEntity()));
    }

    #[Test]
    public function theSectionRendererUsesTheConfiguredTemplate(): void
    {
        $templates = new RecordingRenderer();
        $renderer  = (new TemplateSectionRendererFactory())(new ArrayContainer([
            'config'                         => ['contenir_resource' => ['section_template' => 'app::section']],
            TemplateRendererInterface::class => $templates,
        ]));

        static::assertSame('<div>app::section</div>', $renderer->render(new SectionResourceEntity()));
    }

    #[Test]
    public function theStrategyDefaultsToTheTreeAdapterAndFilesystemCache(): void
    {
        $cache = new Memory();

        $strategy = (new ResourceStrategyFactory())(new ArrayContainer([
            ResourceTreeAdapter::class   => new ResourceTreeAdapter($this->repository()),
            'FilesystemCache'            => $cache,
            WorkflowPluginManager::class => new WorkflowPluginManager(new ArrayContainer()),
        ]));
        $cache->setItem('WorkflowResourceCache', ['route' => ['r' => []], 'navigation' => []]);

        static::assertSame(['r' => []], $strategy->getRouteConfig());
    }

    #[Test]
    public function theStrategyReadsTheWorkflowManagerConfig(): void
    {
        $about = ResourceFactory::make();
        $cache = new Memory();

        $strategy = (new ResourceStrategyFactory())(new ArrayContainer([
            'config'                     => ['workflow_manager' => [
                'repository' => 'adapter',
                'cache'      => 'cache',
                'cache_key'  => 'key',
            ]],
            'adapter'                    => new InMemoryResourceAdapter(new WorkflowResource($about)),
            'cache'                      => $cache,
            WorkflowPluginManager::class => new WorkflowPluginManager(new ArrayContainer()),
        ]));
        $navigation = $strategy->getNavigationConfig();

        static::assertSame(['About', true], [$navigation[0]['label'] ?? null, $cache->hasItem('key')]);
    }

    #[Test]
    public function theStrategyRejectsARepositoryThatIsNotAnAdapter(): void
    {
        $this->expectException(ConfigurationException::class);

        (new ResourceStrategyFactory())(new ArrayContainer([ResourceTreeAdapter::class => new stdClass()]));
    }

    #[Test]
    public function theTreeAdapterWrapsTheRepository(): void
    {
        static::assertInstanceOf(
            ResourceTreeAdapter::class,
            (new ResourceTreeAdapterFactory())(new ArrayContainer([ResourceRepository::class => $this->repository()])),
        );
    }

    #[Test]
    public function theUrlGeneratorUsesTheRouterAndManager(): void
    {
        $generator = (new ResourceUrlGeneratorFactory())(new ArrayContainer([
            RouterInterface::class          => new RecordingRouter(['page-1' => '/about']),
            ResourceManagerInterface::class => new InMemoryResourceManager(ResourceFactory::make()),
        ]));

        static::assertSame('/about', $generator->generate(1)->url);
    }

    private function repository(): ResourceRepository
    {
        return new ResourceRepository(new EntityManager($this->createStub(AdapterInterface::class)));
    }
}

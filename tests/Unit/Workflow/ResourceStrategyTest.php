<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Unit\Workflow;

use Contenir\Resource\Mezzio\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Mezzio\Tests\TestAsset\Handler\PageHandler;
use Contenir\Resource\Mezzio\Tests\TestAsset\Workflow\InMemoryResourceAdapter;
use Contenir\Resource\Mezzio\Tests\TestAsset\Workflow\PageHandlerWorkflow;
use Contenir\Resource\Mezzio\Tests\TestAsset\Workflow\PlainResource;
use Contenir\Resource\Mezzio\Workflow\ResourceStrategy;
use Contenir\Resource\Mezzio\Workflow\WorkflowResource;
use Contenir\Workflow\ResourceInterface;
use Contenir\Workflow\Workflow\WorkflowFactory;
use Contenir\Workflow\Workflow\WorkflowPluginManager;
use DateTimeImmutable;
use Laminas\Cache\Storage\Adapter\Memory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ResourceStrategyTest extends TestCase
{
    private static function strategy(ResourceInterface ...$resources): ResourceStrategy
    {
        return new ResourceStrategy(
            new InMemoryResourceAdapter(...$resources),
            new WorkflowPluginManager(new ServiceManager(), [
                'aliases'   => ['handler' => PageHandlerWorkflow::class],
                'factories' => [PageHandlerWorkflow::class => WorkflowFactory::class],
            ]),
            new Memory(),
        );
    }

    #[Test]
    public function aResourceWithoutTitleKeepsTheWorkflowLabel(): void
    {
        $page        = ResourceFactory::make(resourceId: 1);
        $page->title = null;

        static::assertSame(
            'Untitled',
            self::strategy(new WorkflowResource($page))->getNavigationConfig()[0]['label'] ?? null,
        );
    }

    #[Test]
    public function navigationTakesTheLabelVisibilityAndModifiedDateOfTheResource(): void
    {
        $about = ResourceFactory::make(
            resourceId: 1,
            title: 'About us',
        );
        $about->titleShort = 'About';
        $about->updated    = new DateTimeImmutable('2024-01-02 03:04:05+10:00');
        $team              = ResourceFactory::make(
            resourceId: 2,
            title: 'Team',
        );
        $team->visible = false;
        $about->setChildren([$team]);

        static::assertSame(
            [[
                'label'      => 'About',
                'route'      => 'page-1',
                'visible'    => true,
                'lastmod'    => '2024-01-02T03:04:05+10:00',
                'changefreq' => 'monthly',
                'priority'   => '0.6',
                'pages'      => [[
                    'label'      => 'Team',
                    'route'      => 'page-2',
                    'visible'    => false,
                    'lastmod'    => null,
                    'changefreq' => 'monthly',
                    'priority'   => '0.6',
                    'pages'      => [],
                ]],
            ]],
            self::strategy(new WorkflowResource($about))->getNavigationConfig(),
        );
    }

    #[Test]
    public function otherResourcesUseTheWorkflowMezzioDefaults(): void
    {
        $strategy = self::strategy(new PlainResource());

        static::assertSame(
            [
                [
                    'plain-9' => [
                        'path'       => '/plain',
                        'middleware' => 'PlainHandler',
                        'methods'    => ['GET'],
                        'name'       => 'plain-9',
                        'options'    => ['defaults' => ['id' => ['id' => 9]]],
                    ],
                ],
                [[
                    'label'      => 'Untitled',
                    'route'      => 'plain-9',
                    'visible'    => true,
                    'lastmod'    => null,
                    'changefreq' => 'monthly',
                    'priority'   => '0.6',
                    'pages'      => [],
                ]],
            ],
            [$strategy->getRouteConfig(), $strategy->getNavigationConfig()],
        );
    }

    #[Test]
    public function routesEachResourceThroughTheWorkflowInItsColumn(): void
    {
        $about           = ResourceFactory::make(resourceId: 1);
        $about->slug     = 'about';
        $about->workflow = 'handler';
        $plainPage       = ResourceFactory::make(resourceId: 2);

        static::assertSame(
            [
                'page-1' => [
                    'path'       => '/about',
                    'middleware' => PageHandler::class,
                    'methods'    => ['GET'],
                    'name'       => 'page-1',
                    'options'    => ['defaults' => ['id' => ['resourceId' => 1]]],
                ],
            ],
            self::strategy(new WorkflowResource($about), new WorkflowResource($plainPage))->getRouteConfig(),
        );
    }
}

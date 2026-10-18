<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Unit\Preview;

use Contenir\Preview\InvalidPreview;
use Contenir\Preview\PreviewRecord;
use Contenir\Resource\Mezzio\Preview\WorkflowPageResolver;
use Contenir\Resource\Mezzio\Tests\TestAsset\Handler\PageHandler;
use Contenir\Resource\Mezzio\Tests\TestAsset\Handler\RecordingHandler;
use Contenir\Resource\Mezzio\Tests\TestAsset\Preview\PreviewRecords;
use Contenir\Resource\Mezzio\Tests\TestAsset\Workflow\MiddlewarelessWorkflow;
use Contenir\Resource\Mezzio\Tests\TestAsset\Workflow\PageHandlerWorkflow;
use Contenir\Resource\Mezzio\Tests\TestAsset\Workflow\RecordingHandlerWorkflow;
use Contenir\Workflow\Workflow\WorkflowFactory;
use Contenir\Workflow\Workflow\WorkflowPluginManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Previews of pages resolved to the middleware their workflows name.
 */
#[Group('unit')]
final class WorkflowPageResolverTest extends TestCase
{
    /**
     * @return array<string, array{PreviewRecord}>
     */
    public static function pagesWithoutATemplate(): array
    {
        return [
            'none'   => [PreviewRecords::saved(1)],
            'empty'  => [PreviewRecords::saved(1, ['workflow' => ''])],
            'a list' => [PreviewRecords::saved(1, ['workflow' => ['handler', 'other']])],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function workflowsWithoutMiddleware(): array
    {
        return [
            'one the site does not have' => ['missing'],
            'one with no middleware'     => ['middlewareless'],
        ];
    }

    #[Test]
    public function aPageIsShownByItsWorkflowsMiddlewareWithItsIdAsItsRouteCarriesIt(): void
    {
        $target = (new WorkflowPageResolver($this->workflows()))->resolve(
            PreviewRecords::saved(7, ['workflow' => 'handler']),
        );

        static::assertSame(
            [PageHandler::class, ['id' => ['resourceId' => '7']]],
            [$target->handler, $target->params],
        );
    }

    #[Test]
    public function aPageNotSavedYetIsNotPreviewed(): void
    {
        $this->expectExceptionObject(InvalidPreview::notPreviewable(
            'This page can be previewed once it has been saved.',
        ));

        (new WorkflowPageResolver($this->workflows()))->resolve(PreviewRecords::unsaved(['workflow' => 'handler']));
    }

    #[Test]
    #[DataProvider('workflowsWithoutMiddleware')]
    public function aPageWhoseTemplateHasNoMiddlewareIsNotPreviewed(string $workflow): void
    {
        $this->expectExceptionObject(InvalidPreview::notPreviewable(
            'This page uses a template the site does not have.',
        ));

        (new WorkflowPageResolver($this->workflows()))->resolve(PreviewRecords::saved(7, ['workflow' => $workflow]));
    }

    #[Test]
    #[DataProvider('pagesWithoutATemplate')]
    public function aPageWithoutATemplateIsShownByThePageWorkflow(PreviewRecord $record): void
    {
        $target = (new WorkflowPageResolver($this->workflows()))->resolve($record);

        static::assertSame(RecordingHandler::class, $target->handler);
    }

    private function workflows(): WorkflowPluginManager
    {
        return new WorkflowPluginManager(new ServiceManager(), [
            'aliases'   => [
                'page'           => RecordingHandlerWorkflow::class,
                'handler'        => PageHandlerWorkflow::class,
                'middlewareless' => MiddlewarelessWorkflow::class,
            ],
            'factories' => [
                RecordingHandlerWorkflow::class => WorkflowFactory::class,
                PageHandlerWorkflow::class      => WorkflowFactory::class,
                MiddlewarelessWorkflow::class   => WorkflowFactory::class,
            ],
        ]);
    }
}

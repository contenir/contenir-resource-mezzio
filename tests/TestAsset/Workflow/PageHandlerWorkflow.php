<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\TestAsset\Workflow;

use Contenir\Resource\Mezzio\Tests\TestAsset\Handler\PageHandler;
use Contenir\Workflow\Workflow\AbstractPageWorkflow;

/**
 * A site workflow routing its pages to PageHandler.
 */
final class PageHandlerWorkflow extends AbstractPageWorkflow
{
    protected ?string $middleware = PageHandler::class;
}

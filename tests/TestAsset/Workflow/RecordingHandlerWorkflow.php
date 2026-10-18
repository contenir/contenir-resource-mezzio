<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\TestAsset\Workflow;

use Contenir\Resource\Mezzio\Tests\TestAsset\Handler\RecordingHandler;
use Contenir\Workflow\Workflow\AbstractPageWorkflow;

/**
 * A site workflow routing its pages to RecordingHandler.
 */
final class RecordingHandlerWorkflow extends AbstractPageWorkflow
{
    protected ?string $middleware = RecordingHandler::class;
}

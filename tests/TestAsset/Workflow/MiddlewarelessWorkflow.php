<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\TestAsset\Workflow;

use Contenir\Workflow\Workflow\AbstractPageWorkflow;

/**
 * A site workflow that names no middleware, so its pages have no route.
 */
final class MiddlewarelessWorkflow extends AbstractPageWorkflow {}

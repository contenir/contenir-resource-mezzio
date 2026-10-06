<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\TestAsset\Workflow;

use Contenir\Workflow\ResourceInterface;
use Override;

/**
 * A workflow resource that is not a Contenir resource entity.
 */
final readonly class PlainResource implements ResourceInterface
{
    #[Override]
    public function getChildren(): array
    {
        return [];
    }

    #[Override]
    public function getId(): int
    {
        return 9;
    }

    public function getMiddleware(): string
    {
        return 'PlainHandler';
    }

    #[Override]
    public function getPrimaryKeys(): array
    {
        return ['id' => 9];
    }

    #[Override]
    public function getSlug(): string
    {
        return 'plain';
    }

    #[Override]
    public function getType(): string
    {
        return 'plain';
    }
}

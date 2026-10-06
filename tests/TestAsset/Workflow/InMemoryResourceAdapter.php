<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\TestAsset\Workflow;

use Contenir\Workflow\Repository\ResourceAdapterInterface;
use Contenir\Workflow\ResourceInterface;
use Override;

/**
 * A resource adapter over a fixed list of top-level resources.
 */
final readonly class InMemoryResourceAdapter implements ResourceAdapterInterface
{
    /** @var list<ResourceInterface> */
    private array $resources;

    public function __construct(ResourceInterface ...$resources)
    {
        $this->resources = $resources;
    }

    /**
     * @return list<ResourceInterface>
     */
    #[Override]
    public function getWorkflowResources(): array
    {
        return $this->resources;
    }
}

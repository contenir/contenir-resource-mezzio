<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Workflow;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Workflow\Repository\ResourceAdapterInterface;
use Override;

use function array_map;

/**
 * The workflow-mezzio resource adapter for a site that routes every published
 * resource of some types, wherever it sits: each a workflow resource of its own,
 * routed through the workflow its row names. Unlike ResourceTreeAdapter, a page
 * is reachable while it is published whatever its parent, and other types (a
 * project, a news article) are routed beside the pages.
 *
 * @api
 */
final readonly class ResourceTypesAdapter implements ResourceAdapterInterface
{
    /**
     * @param non-empty-list<non-empty-string> $types
     */
    public function __construct(
        private ResourceManagerInterface $resources,
        private array $types,
    ) {}

    /**
     * @return list<WorkflowResource>
     *
     * @throws DbModelException
     */
    #[Override]
    public function getWorkflowResources(): array
    {
        return array_map(
            static fn(AbstractResourceEntity $resource): WorkflowResource => new WorkflowResource($resource),
            $this->resources->findByType(
                $this->types,
                ['status' => ResourceStatus::Active],
                ['sequence' => 'ASC', 'resourceId' => 'ASC'],
            ),
        );
    }
}

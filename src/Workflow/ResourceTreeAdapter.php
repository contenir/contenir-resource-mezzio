<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Workflow;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Repository\ResourceRepository;
use Contenir\Workflow\Repository\ResourceAdapterInterface;
use Override;

use function array_map;

/**
 * The workflow-mezzio resource adapter: the repository's page tree (active
 * top-level pages and their active descendants), as workflow resources.
 *
 * @api
 */
final readonly class ResourceTreeAdapter implements ResourceAdapterInterface
{
    public function __construct(
        private ResourceRepository $resources,
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
            $this->resources->findPageTree(),
        );
    }
}

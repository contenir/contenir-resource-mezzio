<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Workflow;

use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Exception\MissingResourceException;
use Contenir\Workflow\ResourceInterface;
use Override;

use function array_map;

/**
 * Presents a resource entity, and its attached children, as a
 * workflow-mezzio ResourceInterface. The entity stays framework-neutral;
 * workflows reach it through getEntity().
 *
 * @api
 */
final readonly class WorkflowResource implements ResourceInterface
{
    public function __construct(
        private AbstractResourceEntity $entity,
    ) {}

    /**
     * @return list<WorkflowResource>
     */
    #[Override]
    public function getChildren(): array
    {
        return array_map(
            static fn(AbstractResourceEntity $child): self => new self($child),
            $this->entity->getChildren(),
        );
    }

    public function getEntity(): AbstractResourceEntity
    {
        return $this->entity;
    }

    /**
     * @throws MissingResourceException When the resource has not been saved.
     */
    #[Override]
    public function getId(): int
    {
        return $this->entity->getId();
    }

    /**
     * @return array{resourceId: int}
     *
     * @throws MissingResourceException When the resource has not been saved.
     */
    #[Override]
    public function getPrimaryKeys(): array
    {
        return $this->entity->getPrimaryKeys();
    }

    #[Override]
    public function getSlug(): string
    {
        return $this->entity->getSlug();
    }

    #[Override]
    public function getType(): string
    {
        return $this->entity->getType();
    }
}

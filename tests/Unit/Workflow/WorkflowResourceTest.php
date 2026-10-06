<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Unit\Workflow;

use Contenir\Resource\Core\Exception\MissingResourceException;
use Contenir\Resource\Mezzio\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Mezzio\Workflow\WorkflowResource;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

#[Group('unit')]
final class WorkflowResourceTest extends TestCase
{
    #[Test]
    public function anUnsavedEntityHasNoId(): void
    {
        $this->expectException(MissingResourceException::class);

        (new WorkflowResource(ResourceFactory::make(resourceId: null)))->getPrimaryKeys();
    }

    #[Test]
    public function presentsTheEntityToTheWorkflows(): void
    {
        $entity = ResourceFactory::make(
            resourceId: 4,
            type: 'article',
        );
        $entity->slug = 'news/item';
        $resource     = new WorkflowResource($entity);

        static::assertSame(
            [$entity, 4, ['resourceId' => 4], 'news/item', 'article'],
            [
                $resource->getEntity(),
                $resource->getId(),
                $resource->getPrimaryKeys(),
                $resource->getSlug(),
                $resource->getType(),
            ],
        );
    }

    #[Test]
    public function wrapsTheChildren(): void
    {
        $first  = ResourceFactory::make(resourceId: 2);
        $second = ResourceFactory::make(resourceId: 3);
        $entity = ResourceFactory::make();
        $entity->setChildren([$first, $second]);

        static::assertSame(
            [$first, $second],
            array_map(
                static fn(WorkflowResource $child) => $child->getEntity(),
                (new WorkflowResource($entity))->getChildren(),
            ),
        );
    }
}

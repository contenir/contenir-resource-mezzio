<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Unit\Workflow;

use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Mezzio\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Mezzio\Tests\TestAsset\Manager\InMemoryResourceManager;
use Contenir\Resource\Mezzio\Workflow\ResourceTypesAdapter;
use Contenir\Resource\Mezzio\Workflow\WorkflowResource;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

#[Group('unit')]
final class ResourceTypesAdapterTest extends TestCase
{
    #[Test]
    public function asksForThemInTheAdminsOrder(): void
    {
        $manager = new InMemoryResourceManager();

        (new ResourceTypesAdapter($manager, ['page']))->getWorkflowResources();

        static::assertSame(
            [[
                'findByType',
                [['page'], ['status' => ResourceStatus::Active], ['sequence' => 'ASC', 'resourceId' => 'ASC']],
            ]],
            $manager->calls,
        );
    }

    #[Test]
    public function routesThePublishedResourcesOfItsTypes(): void
    {
        $manager = new InMemoryResourceManager(
            ResourceFactory::make(1, 'page', 'About'),
            ResourceFactory::make(2, 'project', 'Convoy'),
            ResourceFactory::make(3, 'press', 'Vogue'),
            ResourceFactory::make(4, 'page', 'Draft', ResourceStatus::Inactive),
        );

        $resources = (new ResourceTypesAdapter($manager, ['page', 'project']))->getWorkflowResources();

        static::assertSame(
            ['1', '2'],
            array_map(static fn(WorkflowResource $resource): string => (string) $resource->getId(), $resources),
        );
    }
}

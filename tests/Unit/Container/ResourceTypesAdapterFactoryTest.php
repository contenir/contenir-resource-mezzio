<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Unit\Container;

use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Mezzio\Container\ResourceTypesAdapterFactory;
use Contenir\Resource\Mezzio\Tests\TestAsset\Container\ArrayContainer;
use Contenir\Resource\Mezzio\Tests\TestAsset\Manager\InMemoryResourceManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ResourceTypesAdapterFactoryTest extends TestCase
{
    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidTypesProvider(): array
    {
        return [
            'a single name' => ['page'],
            'an empty list' => [[]],
            'a keyed array' => [['a' => 'page']],
            'an empty name' => [['page', '']],
            'a number'      => [['page', 3]],
        ];
    }

    /**
     * @return array<string, array{array<array-key, mixed>, list<string>}>
     */
    public static function typesProvider(): array
    {
        return [
            'the listed types'              => [
                ['workflow_manager' => ['resource_types' => ['page', 'project']]],
                ['page', 'project'],
            ],
            'pages when none are listed'    => [['workflow_manager' => []], ['page']],
            'pages with no workflow config' => [[], ['page']],
        ];
    }

    /**
     * @param array<array-key, mixed> $config
     * @param list<string>            $types
     */
    #[Test]
    #[DataProvider('typesProvider')]
    public function asksForTheTypesTheConfigLists(array $config, array $types): void
    {
        $manager = new InMemoryResourceManager();

        (new ResourceTypesAdapterFactory())($this->container($manager, $config))->getWorkflowResources();

        static::assertSame($types, $manager->calls[0][1][0] ?? null);
    }

    /**
     * @param mixed $types
     */
    #[Test]
    #[DataProvider('invalidTypesProvider')]
    public function refusesTypesThatAreNotAListOfNames(mixed $types): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Config "workflow_manager.resource_types" must be a non-empty list of non-empty strings',
        );

        (new ResourceTypesAdapterFactory())(
            $this->container(new InMemoryResourceManager(), ['workflow_manager' => ['resource_types' => $types]]),
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function container(ResourceManagerInterface $manager, array $config): ArrayContainer
    {
        return new ArrayContainer(['config' => $config, ResourceManagerInterface::class => $manager]);
    }
}

<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Container;

use Contenir\Resource\Core\Container\ServiceLocator;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Mezzio\Workflow\ResourceTypesAdapter;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function array_is_list;
use function is_array;
use function is_string;

/**
 * Builds the ResourceTypesAdapter for the resource types "workflow_manager.resource_types"
 * lists, or for pages alone when it lists none.
 *
 * @api
 */
final class ResourceTypesAdapterFactory
{
    public const string KEY = 'resource_types';

    private static function notTypes(mixed $value): ConfigurationException
    {
        return ConfigurationException::invalidValue(
            ResourceStrategyFactory::SECTION,
            self::KEY,
            'a non-empty list of non-empty strings',
            $value,
        );
    }

    /**
     * @return non-empty-list<non-empty-string>
     *
     * @throws ConfigurationException When the types are not a list of non-empty strings.
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment The config service is untyped; its shape is checked here.
     */
    private static function types(ContainerInterface $container): array
    {
        $config  = $container->has('config') ? $container->get('config') : [];
        $section = is_array($config) ? $config[ResourceStrategyFactory::SECTION] ?? [] : [];
        $types   = is_array($section) ? $section[self::KEY] ?? null : null;
        if (null === $types) {
            return ['page'];
        }

        if (! is_array($types) || [] === $types || ! array_is_list($types)) {
            throw self::notTypes($types);
        }

        $names = [];
        foreach ($types as $type) {
            if (! is_string($type) || '' === $type) {
                throw self::notTypes($types);
            }

            $names[] = $type;
        }

        return $names;
    }

    /**
     * @throws ConfigurationException When the types are not a list of non-empty strings, or a service has the wrong type.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ResourceTypesAdapter
    {
        return new ResourceTypesAdapter(
            ServiceLocator::get($container, ResourceManagerInterface::class, ResourceManagerInterface::class),
            self::types($container),
        );
    }
}

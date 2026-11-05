<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Container;

use Contenir\Resource\Core\Container\ConfigReader;
use Override;
use Psr\Container\ContainerInterface;

use function is_array;

/**
 * The application's container, its config giving "contenir_resource.base_url" the
 * site's URL, for contenir-resource's own factories to read.
 *
 * @internal
 */
final readonly class SiteBaseUrlContainer implements ContainerInterface
{
    public function __construct(
        private ContainerInterface $container,
        private string $baseUrl,
    ) {}

    /**
     * @mago-expect analysis:mixed-assignment Container services are untyped; the config's shape is checked here.
     */
    #[Override]
    public function get(string $id): mixed
    {
        $service = $this->container->get($id);
        if ('config' !== $id || ! is_array($service)) {
            return $service;
        }

        $resource                       = $service[ConfigReader::SECTION] ?? [];
        $service[ConfigReader::SECTION] = [...(is_array($resource) ? $resource : []), 'base_url' => $this->baseUrl];

        return $service;
    }

    #[Override]
    public function has(string $id): bool
    {
        return $this->container->has($id);
    }
}

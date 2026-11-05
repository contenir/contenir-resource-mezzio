<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Container;

use Contenir\Resource\Core\Container\PageMetadataBuilderFactory;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Mezzio\Config\SiteBaseUrl;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function is_array;

/**
 * Gives the page metadata builder the site's URL from its Site Settings as its
 * base URL when contenir-resource's config names none, so canonical and share
 * addresses never take the request's host on a site that names its address.
 * The builder is otherwise built as contenir-resource builds it.
 *
 * @api
 */
final class SiteBaseUrlDelegator
{
    /**
     * @param callable(): PageMetadataBuilder $callback
     *
     * @throws ConfigurationException When contenir-resource's config is not usable.
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment The config service is untyped; its shape is checked here.
     * @mago-expect analysis:unused-parameter The service name is fixed: this delegator is registered on PageMetadataBuilder alone.
     */
    public function __invoke(ContainerInterface $container, string $name, callable $callback): PageMetadataBuilder
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $url    = SiteBaseUrl::of(is_array($config) ? $config : []);
        if (null === $url) {
            return $callback();
        }

        return (new PageMetadataBuilderFactory())(new SiteBaseUrlContainer($container, $url));
    }
}

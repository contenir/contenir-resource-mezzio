<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Container;

use Contenir\Resource\Core\Container\ServiceLocator;
use Contenir\Resource\Core\Content\ResourceSummary;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Mezzio\Url\ResourceUrlGenerator;
use Contenir\Resource\Mezzio\View\Helper\ResourceContentHelper;
use Contenir\Resource\Mezzio\View\Helper\ResourceHelper;
use Contenir\Resource\Mezzio\View\Helper\ResourceMetaHelper;
use Contenir\Resource\Mezzio\View\Helper\ResourceUrlHelper;
use Laminas\View\Helper\HeadLink;
use Laminas\View\Helper\HeadMeta;
use Laminas\View\Helper\HeadTitle;
use Laminas\View\HelperPluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function sprintf;

/**
 * Builds the four laminas-view helpers. resourceMeta writes to the head
 * helpers of the application's HelperPluginManager, the one the
 * laminas-view renderer uses.
 *
 * @api
 */
final class ViewHelperFactory
{
    /**
     * @throws ConfigurationException When a head helper has the wrong type.
     * @throws ContainerExceptionInterface
     */
    private static function metaHelper(HelperPluginManager $helpers): ResourceMetaHelper
    {
        return new ResourceMetaHelper(
            ServiceLocator::get($helpers, HeadTitle::class, HeadTitle::class),
            ServiceLocator::get($helpers, HeadLink::class, HeadLink::class),
            ServiceLocator::get($helpers, HeadMeta::class, HeadMeta::class),
        );
    }

    /**
     * @throws ConfigurationException When a service has the wrong type or the helper is unknown.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
    ): ResourceHelper|ResourceContentHelper|ResourceMetaHelper|ResourceUrlHelper {
        return match ($requestedName) {
            ResourceHelper::class => new ResourceHelper(
                ServiceLocator::get($container, ResourceManagerInterface::class, ResourceManagerInterface::class),
            ),
            ResourceContentHelper::class => new ResourceContentHelper(
                ServiceLocator::get($container, ResourceSummary::class, ResourceSummary::class),
            ),
            ResourceUrlHelper::class => new ResourceUrlHelper(
                ServiceLocator::get($container, ResourceUrlGenerator::class, ResourceUrlGenerator::class),
            ),
            ResourceMetaHelper::class => self::metaHelper(
                ServiceLocator::get($container, HelperPluginManager::class, HelperPluginManager::class),
            ),
            default                      => throw new ConfigurationException(sprintf(
                'No view helper "%s" is built by this factory',
                $requestedName,
            )),
        };
    }
}

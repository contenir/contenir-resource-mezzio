<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Container;

use Contenir\Resource\Core\Container\ConfigReader;
use Contenir\Resource\Core\Container\ServiceLocator;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Mezzio\Content\TemplateSectionRenderer;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Builds the TemplateSectionRenderer with the application's template
 * renderer and the "contenir_resource.section_template" template name.
 *
 * @api
 */
final class TemplateSectionRendererFactory
{
    /**
     * @throws ConfigurationException When section_template is not set or the renderer has the wrong type.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): TemplateSectionRenderer
    {
        $template = ConfigReader::fromContainer($container)->optionalString('section_template');

        return new TemplateSectionRenderer(
            ServiceLocator::get($container, TemplateRendererInterface::class, TemplateRendererInterface::class),
            $template ?? throw ConfigurationException::invalidValue(
                ConfigReader::SECTION,
                'section_template',
                'a template name',
                $template,
            ),
        );
    }
}

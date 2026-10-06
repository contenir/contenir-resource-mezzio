<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Content;

use Contenir\Resource\Core\Content\SectionAwareInterface;
use Contenir\Resource\Core\Content\SectionRendererInterface;
use Mezzio\Template\TemplateRendererInterface;
use Override;

/**
 * Renders section content through the application's template renderer:
 * the template receives "section" (the section content) and "resource".
 *
 * @api
 */
final readonly class TemplateSectionRenderer implements SectionRendererInterface
{
    public function __construct(
        private TemplateRendererInterface $renderer,
        private string $template,
    ) {}

    #[Override]
    public function render(SectionAwareInterface $resource): string
    {
        return $this->renderer->render($this->template, [
            'section'  => $resource->getSection(),
            'resource' => $resource,
        ]);
    }
}

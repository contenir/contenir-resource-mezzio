<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Unit\Content;

use Contenir\Resource\Mezzio\Content\TemplateSectionRenderer;
use Contenir\Resource\Mezzio\Tests\TestAsset\Entity\SectionResourceEntity;
use Contenir\Resource\Mezzio\Tests\TestAsset\Template\RecordingRenderer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class TemplateSectionRendererTest extends TestCase
{
    #[Test]
    public function rendersTheTemplateWithTheSectionAndResource(): void
    {
        $templates = new RecordingRenderer();
        $resource  = new SectionResourceEntity();

        $html = (new TemplateSectionRenderer($templates, 'app::section'))->render($resource);

        static::assertSame(
            [
                '<div>app::section</div>',
                [[
                    'app::section',
                    [
                        'section'  => ['heading' => 'Section heading'],
                        'resource' => $resource,
                    ],
                ]],
            ],
            [$html, $templates->rendered],
        );
    }
}

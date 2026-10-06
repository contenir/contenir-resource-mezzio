<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\TestAsset\Template;

use LogicException;
use Mezzio\Template\TemplatePath;
use Mezzio\Template\TemplateRendererInterface;
use Override;

/**
 * Renders "<name>" with the params it was given recorded.
 */
final class RecordingRenderer implements TemplateRendererInterface
{
    /** @var list<array{string, array<array-key, mixed>|object}> */
    public array $rendered = [];

    #[Override]
    public function addDefaultParam(string $templateName, string $param, $value): void
    {
        throw new LogicException('Not used');
    }

    #[Override]
    public function addPath(string $path, ?string $namespace = null): void
    {
        throw new LogicException('Not used');
    }

    /**
     * @return list<TemplatePath>
     */
    #[Override]
    public function getPaths(): array
    {
        return [];
    }

    #[Override]
    public function render(string $name, $params = []): string
    {
        $this->rendered[] = [$name, $params];

        return "<div>{$name}</div>";
    }
}

<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Unit\Url;

use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Mezzio\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Mezzio\Tests\TestAsset\Manager\InMemoryResourceManager;
use Contenir\Resource\Mezzio\Tests\TestAsset\Router\RecordingRouter;
use Contenir\Resource\Mezzio\Url\ResourceUrlGenerator;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ResourceUrlGeneratorTest extends TestCase
{
    private InMemoryResourceManager $manager;

    #[Test]
    public function anEmptyResourceIdFallsBackToTheUrl(): void
    {
        $generator = $this->generator();

        static::assertSame(
            [['/contact', '_blank'], []],
            [$generator->generate('', url: '/contact')->toArray(), $this->manager->calls],
        );
    }

    #[Test]
    public function anExternalUrlOpensInANewWindow(): void
    {
        static::assertSame(
            ['https://example.com/x', '_blank'],
            $this->generator()
                ->generate(
                    url: 'example.com/x',
                    target: '_self',
                )
                ->toArray(),
        );
    }

    #[Test]
    public function anInactiveOrUnknownResourceHasNoUrl(): void
    {
        $generator = $this->generator();

        static::assertSame(
            [[null, 't'], [null, null]],
            [$generator->generate(3, target: 't')->toArray(), $generator->generate(99)->toArray()],
        );
    }

    #[Test]
    public function anUnsavedEntityHasNoUrl(): void
    {
        static::assertSame(
            [null, null],
            $this->generator()->generate(ResourceFactory::make(resourceId: null))->toArray(),
        );
    }

    #[Test]
    public function aRejectedUrlHasNoUrl(): void
    {
        static::assertSame([null, '_blank'], $this->generator()->generate(url: 'javascript:alert(1)')->toArray());
    }

    #[Test]
    public function aResourceWithoutARouteHasNoUrl(): void
    {
        static::assertSame([null, null], $this->generator()->generate(2)->toArray());
    }

    #[Test]
    public function linksAnActiveResourceById(): void
    {
        $generator = $this->generator();

        static::assertSame(
            [['/about', null], ['/about', 'x'], [['findActive', 1], ['findActive', '1']]],
            [
                $generator->generate(1)->toArray(),
                $generator->generate('1', url: 'https://ignored.test', target: 'x')->toArray(),
                $this->manager->calls,
            ],
        );
    }

    #[Test]
    public function linksAnEntityThroughItsRouteKeepingTheTarget(): void
    {
        static::assertSame(
            ['/about', '_self'],
            $this->generator()->generate(ResourceFactory::make(resourceId: 1), target: '_self')->toArray(),
        );
    }

    #[Test]
    public function nothingToLinkToKeepsTheTarget(): void
    {
        $generator = $this->generator();

        static::assertSame(
            [[null, 't'], [null, null]],
            [
                $generator->generate(
                    url: '',
                    target: 't',
                )
                    ->toArray(),
                $generator->generate()->toArray(),
            ],
        );
    }

    private function generator(): ResourceUrlGenerator
    {
        $this->manager = new InMemoryResourceManager(
            ResourceFactory::make(resourceId: 1),
            ResourceFactory::make(
                resourceId: 2,
                type: 'article',
            ),
            ResourceFactory::make(
                resourceId: 3,
                status: ResourceStatus::Pending,
            ),
        );

        return new ResourceUrlGenerator(
            new RecordingRouter(['page-1' => '/about', 'page-3' => '/draft']),
            $this->manager,
        );
    }
}

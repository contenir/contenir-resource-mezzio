<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Unit\Container;

use Contenir\Resource\Core\Metadata\ImageUrlResolverInterface;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Core\Metadata\PathImageUrlResolver;
use Contenir\Resource\Mezzio\Container\SiteBaseUrlDelegator;
use Contenir\Resource\Mezzio\Tests\TestAsset\Container\ArrayContainer;
use Contenir\Resource\Mezzio\Tests\TestAsset\Entity\ResourceFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SiteBaseUrlDelegatorTest extends TestCase
{
    #[Test]
    public function leavesTheBuilderAsItIsWhenResourceNamesItsOwnBaseUrl(): void
    {
        $own = new PageMetadataBuilder(baseUrl: 'https://cdn.test');

        static::assertSame($own, $this->delegate(
            ['site' => ['url' => 'https://www.site.test'], 'contenir_resource' => ['base_url' => 'https://cdn.test']],
            $own,
        ));
    }

    #[Test]
    public function leavesTheBuilderAsItIsWithNoConfig(): void
    {
        $own = new PageMetadataBuilder();

        static::assertSame($own, (new SiteBaseUrlDelegator())(
            new ArrayContainer(),
            PageMetadataBuilder::class,
            static fn(): PageMetadataBuilder => $own,
        ));
    }

    #[Test]
    public function namesPagesAtTheSitesUrlNotTheRequestsHost(): void
    {
        $builder = $this->delegate([
            'site'              => ['url' => 'https://www.site.test'],
            'contenir_resource' => ['site_name' => 'Site'],
        ]);

        static::assertSame(
            'https://www.site.test/about',
            $builder->build(ResourceFactory::make(), 'http://other.test/about')->url,
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function delegate(array $config, ?PageMetadataBuilder $own = null): PageMetadataBuilder
    {
        $container = new ArrayContainer([
            'config'                         => $config,
            ImageUrlResolverInterface::class => new PathImageUrlResolver(),
        ]);

        return (new SiteBaseUrlDelegator())(
            $container,
            PageMetadataBuilder::class,
            static fn(): PageMetadataBuilder => $own ?? new PageMetadataBuilder(),
        );
    }
}

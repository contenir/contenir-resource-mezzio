<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Unit\Container;

use Contenir\Resource\Mezzio\Container\SiteBaseUrlContainer;
use Contenir\Resource\Mezzio\Tests\TestAsset\Container\ArrayContainer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[Group('unit')]
final class SiteBaseUrlContainerTest extends TestCase
{
    #[Test]
    public function givesResourcesConfigTheBaseUrlBesideItsOtherSettings(): void
    {
        $container = new SiteBaseUrlContainer(
            new ArrayContainer([
                'config' => ['contenir_resource' => ['site_name' => 'Site'], 'site' => ['url' => 'x']],
            ]),
            'https://www.site.test',
        );

        static::assertSame(
            [
                'contenir_resource' => ['site_name' => 'Site', 'base_url' => 'https://www.site.test'],
                'site'              => ['url' => 'x'],
            ],
            $container->get('config'),
        );
    }

    #[Test]
    public function passesOtherServicesThrough(): void
    {
        $service   = new stdClass();
        $container = new SiteBaseUrlContainer(new ArrayContainer(['other' => $service]), 'https://www.site.test');

        static::assertSame([$service, true, false], [
            $container->get('other'),
            $container->has('other'),
            $container->has('none'),
        ]);
    }

    #[Test]
    public function startsResourcesConfigWhenThereIsNone(): void
    {
        $container = new SiteBaseUrlContainer(new ArrayContainer(['config' => []]), 'https://www.site.test');

        static::assertSame(['contenir_resource' => ['base_url' => 'https://www.site.test']], $container->get('config'));
    }
}

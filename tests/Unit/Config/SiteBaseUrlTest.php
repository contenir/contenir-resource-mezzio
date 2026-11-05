<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Unit\Config;

use Contenir\Resource\Mezzio\Config\SiteBaseUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SiteBaseUrlTest extends TestCase
{
    /**
     * @return array<string, array{array<array-key, mixed>, ?string}>
     */
    public static function configProvider(): array
    {
        $site = ['site' => ['url' => 'https://www.site.test']];

        return [
            'the site settings url'               => [$site, 'https://www.site.test'],
            'the cms overlay over it'             => [
                [...$site, 'settings' => ['site' => ['url' => 'https://new.site.test']]],
                'https://new.site.test',
            ],
            'an overlay that leaves the url'      => [
                [...$site, 'settings' => ['site' => ['name' => 'Site']]],
                'https://www.site.test',
            ],
            'trimmed'                             => [
                ['site' => ['url' => ' https://www.site.test ']],
                'https://www.site.test',
            ],
            'none when resource names a base url' => [
                [...$site, 'contenir_resource' => ['base_url' => 'https://cdn.test']],
                null,
            ],
            'resource config without a base url'  => [
                [...$site, 'contenir_resource' => ['site_name' => 'Site']],
                'https://www.site.test',
            ],
            'none when the url is blank'          => [['site' => ['url' => '  ']], null],
            'none when the url is not a string'   => [['site' => ['url' => 3]], null],
            'none when the settings name no site' => [[], null],
            'none when site is not a section'     => [['site' => 'https://www.site.test'], null],
            'an overlay that is not a section'    => [[...$site, 'settings' => 'x'], 'https://www.site.test'],
        ];
    }

    /**
     * @param array<array-key, mixed> $config
     */
    #[Test]
    #[DataProvider('configProvider')]
    public function readsTheSitesUrlFromItsSettings(array $config, ?string $expected): void
    {
        static::assertSame($expected, SiteBaseUrl::of($config));
    }
}

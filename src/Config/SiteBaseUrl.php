<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Config;

use Contenir\Resource\Core\Container\ConfigReader;

use function array_replace_recursive;
use function is_array;
use function is_string;
use function trim;

/**
 * The base URL a Mezzio site's Site Settings give, for page metadata that names
 * no base URL of its own: "site.url", as the CMS writes it. settings.global.php
 * merges at the top level of the application's config and settings.local.php,
 * which the CMS saves an editor's changes to, under "settings", laid over it.
 *
 * Without a base URL, canonical and share addresses take the request's host, so
 * a site whose Site Settings name its address gets that address without any
 * config of its own. A base URL set in "contenir_resource" is left as it is.
 *
 * @internal
 */
final class SiteBaseUrl
{
    /**
     * The site's URL, or null when "contenir_resource.base_url" is set or the
     * site's settings name no URL.
     *
     * @param array<array-key, mixed> $config The application's config
     *
     * @mago-expect analysis:mixed-assignment The application's config is untyped; each level is checked here.
     */
    public static function of(array $config): ?string
    {
        $resource = $config[ConfigReader::SECTION] ?? null;
        if (is_array($resource) && null !== ($resource['base_url'] ?? null)) {
            return null;
        }

        $local    = $config['settings'] ?? null;
        $settings = is_array($local) ? array_replace_recursive($config, $local) : $config;
        $site     = $settings['site'] ?? null;
        $url      = is_array($site) && is_string($site['url'] ?? null) ? trim($site['url']) : '';

        return '' === $url ? null : $url;
    }
}

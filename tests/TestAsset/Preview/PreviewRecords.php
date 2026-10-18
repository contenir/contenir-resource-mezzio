<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\TestAsset\Preview;

use Contenir\Preview\PreviewRecord;

/**
 * Previews of the site's pages as the CMS sends them, keyed as it keys the resource table.
 */
final class PreviewRecords
{
    /**
     * @param array<string, string|list<string>|null> $values
     */
    public static function saved(int $resourceId, array $values = []): PreviewRecord
    {
        return new PreviewRecord('site.page.home', ['resource_id' => (string) $resourceId], $values);
    }

    /**
     * @param array<string, string|list<string>|null> $values
     */
    public static function unsaved(array $values = []): PreviewRecord
    {
        return new PreviewRecord('site.page.home', [], $values);
    }
}

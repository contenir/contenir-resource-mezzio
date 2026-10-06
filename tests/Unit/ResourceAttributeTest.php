<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Unit;

use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Exception\MissingResourceException;
use Contenir\Resource\Core\Metadata\PageMetadata;
use Contenir\Resource\Mezzio\ResourceAttribute;
use Contenir\Resource\Mezzio\Tests\TestAsset\Entity\ResourceFactory;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ResourceAttributeTest extends TestCase
{
    #[Test]
    public function attributesOfAnotherTypeReadAsMissing(): void
    {
        $request = (new ServerRequest())->withAttribute(ResourceAttribute::RESOURCE, 'about')
            ->withAttribute(ResourceAttribute::METADATA, 'meta');

        static::assertSame(
            [null, null],
            [ResourceAttribute::resource($request), ResourceAttribute::metadata($request)],
        );
    }

    #[Test]
    public function readsTheResolvedResourceAndMetadata(): void
    {
        $resource = ResourceFactory::make();
        $metadata = new PageMetadata('https://s.test/');
        $request  = (new ServerRequest())->withAttribute(ResourceAttribute::RESOURCE, $resource)
            ->withAttribute(ResourceAttribute::METADATA, $metadata);

        static::assertSame(
            [$resource, $resource, $metadata],
            [
                ResourceAttribute::resource($request),
                ResourceAttribute::require($request),
                ResourceAttribute::metadata($request),
            ],
        );
    }

    #[Test]
    public function requireThrowsWithoutAResolvedResource(): void
    {
        $this->expectException(MissingResourceException::class);
        $this->expectExceptionMessage(
            'No resource was resolved for this request; route the request through a resource workflow and pipe '
                . 'ResourceMiddleware after RouteMiddleware',
        );

        ResourceAttribute::require(new ServerRequest());
    }

    #[Test]
    public function theAttributesAreKeyedByClassName(): void
    {
        static::assertSame(
            [AbstractResourceEntity::class, PageMetadata::class],
            [ResourceAttribute::RESOURCE, ResourceAttribute::METADATA],
        );
    }
}

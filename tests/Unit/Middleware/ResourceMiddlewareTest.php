<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Unit\Middleware;

use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Mezzio\Middleware\ResourceMiddleware;
use Contenir\Resource\Mezzio\ResourceAttribute;
use Contenir\Resource\Mezzio\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Mezzio\Tests\TestAsset\Handler\RecordingHandler;
use Contenir\Resource\Mezzio\Tests\TestAsset\Manager\InMemoryResourceManager;
use Laminas\Diactoros\ServerRequest;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('middleware')]
final class ResourceMiddlewareTest extends TestCase
{
    private RecordingHandler $handler;

    private InMemoryResourceManager $manager;

    private ResourceMiddleware $middleware;

    private RecordingHandler $notFound;

    /**
     * @return array<string, array{mixed}>
     */
    public static function otherRouteProvider(): array
    {
        return [
            'no id'                  => [null],
            'path placeholder id'    => ['5'],
            'array of another shape' => [['page_id' => 1]],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function unusableIdProvider(): array
    {
        return [
            'unknown id' => [99],
            'array id'   => [[1]],
            'null id'    => [null],
            'float id'   => [1.0],
        ];
    }

    #[Test]
    public function acceptsADigitStringId(): void
    {
        $this->middleware->process((new ServerRequest())->withAttribute('id', ['resourceId' => '1']), $this->handler);

        static::assertSame([['findActive', '1']], $this->manager->calls);
    }

    #[Test]
    public function anInactiveResourceIsAnsweredByTheNotFoundHandler(): void
    {
        $request  = (new ServerRequest())->withAttribute('id', ['resourceId' => 2]);
        $response = $this->middleware->process($request, $this->handler);

        static::assertSame(
            [404, $request, null],
            [$response->getStatusCode(), $this->notFound->request, $this->handler->request],
        );
    }

    #[Test]
    public function aNonScalarIdIsNeverLookedUp(): void
    {
        $this->middleware->process((new ServerRequest())->withAttribute('id', ['resourceId' => [1]]), $this->handler);

        static::assertSame([], $this->manager->calls);
    }

    #[Test]
    #[DataProvider('unusableIdProvider')]
    public function anUnusableIdIsAnsweredByTheNotFoundHandler(mixed $resourceId): void
    {
        $response = $this->middleware->process(
            (new ServerRequest())->withAttribute('id', ['resourceId' => $resourceId]),
            $this->handler,
        );

        static::assertSame([404, null], [$response->getStatusCode(), $this->handler->request]);
    }

    #[Test]
    public function attachesTheActiveResourceAndItsMetadata(): void
    {
        $request = new ServerRequest(uri: 'https://evil.test/about?x=1');
        $this->middleware->process($request->withAttribute('id', ['resourceId' => 1]), $this->handler);

        $handled  = $this->handler->request;
        $metadata = null === $handled ? null : ResourceAttribute::metadata($handled);

        static::assertSame(
            [1, 'https://www.s.test/about', 'All about us', [['findActive', 1]]],
            [
                null === $handled ? null : ResourceAttribute::resource($handled)?->resourceId,
                $metadata?->url,
                $metadata?->description,
                $this->manager->calls,
            ],
        );
    }

    #[Test]
    #[DataProvider('otherRouteProvider')]
    public function otherRoutesPassThroughUntouched(mixed $id): void
    {
        $request  = (new ServerRequest())->withAttribute('id', $id);
        $response = $this->middleware->process($request, $this->handler);

        static::assertSame(
            ['handled', $request, []],
            [(string) $response->getBody(), $this->handler->request, $this->manager->calls],
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $about                  = ResourceFactory::make(resourceId: 1);
        $about->metaDescription = 'All about us';
        $this->manager          = new InMemoryResourceManager(
            $about,
            ResourceFactory::make(
                resourceId: 2,
                status: ResourceStatus::Inactive,
            ),
        );
        $this->handler  = new RecordingHandler();
        $this->notFound = new RecordingHandler(
            status: 404,
            body: 'not found',
        );
        $this->middleware = new ResourceMiddleware(
            $this->manager,
            new PageMetadataBuilder(baseUrl: 'https://www.s.test'),
            $this->notFound,
        );
    }
}

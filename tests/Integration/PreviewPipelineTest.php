<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Integration;

use Contenir\Preview\Clock\SystemClock;
use Contenir\Preview\Mezzio\ConfigProvider as PreviewConfigProvider;
use Contenir\Preview\Mezzio\PreviewMiddleware;
use Contenir\Preview\PreviewConfig;
use Contenir\Preview\PreviewRecord;
use Contenir\Preview\Token\TokenSigner;
use Contenir\Resource\Mezzio\Middleware\ResourceMiddleware;
use Contenir\Resource\Mezzio\Preview\WorkflowPageResolver;
use Contenir\Resource\Mezzio\Tests\TestAsset\Preview\PreviewRecords;
use Contenir\Resource\Mezzio\Tests\Trait\ApplicationContainerTrait;
use Contenir\Resource\Mezzio\Tests\Trait\SqliteDatabaseTrait;
use Contenir\Resource\Mezzio\Tests\Trait\TemporaryDirectoryTrait;
use Laminas\Diactoros\ConfigProvider as DiactorosConfigProvider;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Application;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

use function str_starts_with;

/**
 * CMS previews of pages through a real Mezzio pipeline with contenir-preview-mezzio piped before
 * routing: a page at its workflow's middleware, loaded by ResourceMiddleware with the editor's
 * unsaved values, drafts included, while an ordinary visit sees the site as stored.
 */
#[Group('integration')]
#[Group('middleware')]
final class PreviewPipelineTest extends TestCase
{
    use ApplicationContainerTrait;
    use SqliteDatabaseTrait;
    use TemporaryDirectoryTrait;

    private const string SHARED = 'a-secret-of-at-least-thirty-two-bytes!!';

    #[Test]
    public function aDraftPageIsPreviewed(): void
    {
        $response = $this->preview(PreviewRecords::saved(4, ['workflow' => 'handler']));

        static::assertSame([200, true], [
            $response->getStatusCode(),
            str_starts_with((string) $response->getBody(), "resource 4\n<title>Spa (draft)</title>"),
        ]);
    }

    #[Test]
    public function anOrdinaryVisitDoesNotSeeADraft(): void
    {
        static::assertSame(404, $this->visit('/spa')->getStatusCode());
    }

    #[Test]
    public function anOrdinaryVisitSeesTheSiteAsStored(): void
    {
        $response = $this->visit('/about');

        static::assertSame([200, true, false], [
            $response->getStatusCode(),
            str_starts_with((string) $response->getBody(), "resource 1\n<title>About &lt;us&gt;</title>"),
            $response->hasHeader('X-Robots-Tag'),
        ]);
    }

    #[Test]
    public function aPageIsPreviewedWithItsUnsavedValues(): void
    {
        $response = $this->preview(PreviewRecords::saved(1, ['title' => 'About us, unsaved', 'workflow' => 'handler']));

        static::assertSame(
            [200, true, 'noindex, nofollow'],
            [
                $response->getStatusCode(),
                str_starts_with((string) $response->getBody(), "resource 1\n<title>About us, unsaved</title>"),
                $response->getHeaderLine('X-Robots-Tag'),
            ],
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpTemporaryDirectory();
        $this->setUpDatabase();
        $this->insertResource([
            'resource_id' => 1,
            'slug'        => 'about',
            'title'       => 'About <us>',
            'workflow'    => 'handler',
        ]);
        $this->insertResource([
            'resource_id' => 4,
            'slug'        => 'spa',
            'title'       => 'Spa (draft)',
            'workflow'    => 'handler',
            'active'      => 'pending',
        ]);
        $this->setUpApplicationContainer(
            ['base_url' => 'https://www.site.test'],
            [DiactorosConfigProvider::class, PreviewConfigProvider::class],
            [
                'contenir_preview' => [
                    'issuer'    => 'https://admin.site.test',
                    'audience'  => 'https://www.site.test',
                    'secrets'   => ['test' => self::SHARED],
                    'sign_with' => 'test',
                    'replay'    => ['directory' => $this->tmpDir],
                    'resolvers' => ['site.page.home' => WorkflowPageResolver::class],
                ],
            ],
        );

        $app = $this->container->get(Application::class);
        $app->pipe(PreviewMiddleware::class);
        $app->pipe(RouteMiddleware::class);
        $app->pipe(ResourceMiddleware::class);
        $app->pipe(DispatchMiddleware::class);
        $app->pipe(NotFoundHandler::class);
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->tearDownTemporaryDirectory();
    }

    private function preview(PreviewRecord $record): ResponseInterface
    {
        $config = new PreviewConfig(
            'https://admin.site.test',
            'https://www.site.test',
            ['test' => self::SHARED],
            'test',
        );

        return $this->container->get(Application::class)->handle(new ServerRequest(
            uri: 'https://www.site.test/api/resource/preview',
            method: 'POST',
            parsedBody: ['contenir_preview' => (new TokenSigner($config, new SystemClock()))->sign($record, '12')],
        ));
    }

    private function visit(string $path): ResponseInterface
    {
        return $this->container->get(Application::class)->handle(new ServerRequest(
            uri: "https://www.site.test{$path}",
            method: 'GET',
        ));
    }
}

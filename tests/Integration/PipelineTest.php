<?php

declare(strict_types=1);

namespace Contenir\Resource\Mezzio\Tests\Integration;

use Contenir\Db\Model\EntityManager;
use Contenir\Resource\Mezzio\Middleware\ResourceMiddleware;
use Contenir\Resource\Mezzio\Tests\Trait\ApplicationContainerTrait;
use Contenir\Resource\Mezzio\Tests\Trait\SqliteDatabaseTrait;
use Contenir\Resource\Mezzio\Tests\Trait\TemporaryDirectoryTrait;
use Contenir\Resource\Mezzio\Url\ResourceUrlGenerator;
use Contenir\Resource\Mezzio\Workflow\ResourceStrategy;
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

use function strstr;

#[Group('integration')]
#[Group('middleware')]
final class PipelineTest extends TestCase
{
    use ApplicationContainerTrait;
    use SqliteDatabaseTrait;
    use TemporaryDirectoryTrait;

    #[Test]
    public function aHiddenChildPageIsStillRouted(): void
    {
        static::assertSame("resource 2\n", strstr(
            (string) $this->get('https://www.site.test/about/team')->getBody(),
            needle: '<',
            before_needle: true,
        ));
    }

    #[Test]
    public function anUnknownPathIsNotFound(): void
    {
        static::assertSame(404, $this->get('https://www.site.test/nowhere')->getStatusCode());
    }

    #[Test]
    public function aPageDeactivatedAfterItsRouteWasCachedIsNotFound(): void
    {
        $this->container->get(Application::class);
        $this->pdo->exec("UPDATE resource SET active = 'archived' WHERE resource_id = 1");
        $this->container->get(EntityManager::class)->clear();

        static::assertSame(404, $this->get('https://www.site.test/about')->getStatusCode());
    }

    #[Test]
    public function aPageWhoseWorkflowHasNoHandlerHasNoRoute(): void
    {
        static::assertSame(404, $this->get('https://www.site.test/contact')->getStatusCode());
    }

    #[Test]
    public function aResourcePageIsRenderedWithItsMetadata(): void
    {
        $response = $this->get('https://evil.test/about?utm=x');

        static::assertSame(
            [
                200,
                "resource 1\n<title>About &lt;us&gt;</title>\n"
                    . "<link rel=\"canonical\" href=\"https://www.site.test/about\">\n"
                    . "<meta property=\"og:type\" content=\"website\">\n"
                    . "<meta property=\"og:url\" content=\"https://www.site.test/about\">\n"
                    . "<meta property=\"twitter:url\" content=\"https://www.site.test/about\">\n"
                    . "<meta property=\"og:title\" content=\"About &lt;us&gt;\">\n"
                    . "<meta property=\"twitter:title\" content=\"About &lt;us&gt;\">\n"
                    . "<meta name=\"description\" content=\"Fish &amp; chips\">\n"
                    . "<meta property=\"og:description\" content=\"Fish &amp; chips\">\n"
                    . "<meta property=\"twitter:description\" content=\"Fish &amp; chips\">\n"
                    . '<meta property="og:updated_time" content="2024-01-02T03:04:05+00:00">',
            ],
            [$response->getStatusCode(), (string) $response->getBody()],
        );
    }

    #[Test]
    public function navigationFollowsThePageTree(): void
    {
        $navigation = $this->container->get(ResourceStrategy::class)->getNavigationConfig();

        static::assertSame(
            [
                ['About <us>', 'page-1', true, null],
                ['Team', 'article-2', false],
                ['Contact', 'page-3', true],
            ],
            [
                [
                    $navigation[0]['label'] ?? null,
                    $navigation[0]['route'] ?? null,
                    $navigation[0]['visible'] ?? null,
                    $navigation[0]['lastmod'] ?? null,
                ],
                [
                    $navigation[0]['pages'][0]['label'] ?? null,
                    $navigation[0]['pages'][0]['route'] ?? null,
                    $navigation[0]['pages'][0]['visible'] ?? null,
                ],
                [$navigation[1]['label'] ?? null, $navigation[1]['route'] ?? null, $navigation[1]['visible'] ?? null],
            ],
        );
    }

    #[Test]
    public function urlsAreGeneratedFromTheRegisteredRoutes(): void
    {
        $this->container->get(Application::class);
        $urls = $this->container->get(ResourceUrlGenerator::class);

        static::assertSame(
            [
                ['/about',              null],
                ['/about/team',         '_self'],
                [null,                  null],
                ['https://example.com', '_blank'],
            ],
            [
                $urls->generate(1)->toArray(),
                $urls->generate('2', target: '_self')->toArray(),
                $urls->generate(3)->toArray(),
                $urls->generate(url: 'example.com')->toArray(),
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
            'description' => '<p>Fish &amp; chips</p>',
            'created'     => '2024-01-02 03:04:05',
            'sequence'    => 1,
        ]);
        $this->insertResource([
            'resource_id'      => 2,
            'parent_id'        => 1,
            'resource_type_id' => 'article',
            'slug'             => 'about/team',
            'title'            => 'Team',
            'workflow'         => 'handler',
            'visible'          => 0,
        ]);
        $this->insertResource(['resource_id' => 3, 'slug' => 'contact', 'title' => 'Contact', 'sequence' => 2]);
        $this->setUpApplicationContainer(['base_url' => 'https://www.site.test']);

        $app = $this->container->get(Application::class);
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

    private function get(string $url): ResponseInterface
    {
        $app = $this->container->get(Application::class);

        return $app->handle(new ServerRequest(
            uri: $url,
            method: 'GET',
        ));
    }
}

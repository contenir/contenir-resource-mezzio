# contenir/contenir-resource-mezzio

[![Continuous Integration](https://github.com/contenir/contenir-resource-mezzio/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-resource-mezzio/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/contenir/contenir-resource-mezzio/graph/badge.svg)](https://codecov.io/gh/contenir/contenir-resource-mezzio)

The [Mezzio](https://docs.mezzio.dev/) adapter for
[contenir/contenir-resource](https://github.com/contenir/contenir-resource), so a Mezzio site that does not run the
full Contenir CMS can serve Contenir resources (pages, articles, collections):

- routes and navigation built from the resource tree through
  [contenir-workflow-mezzio](https://github.com/contenir/contenir-workflow-mezzio);
- a PSR-15 middleware that resolves the routed resource, refuses unpublished ones, and attaches the resource and its
  page metadata to the request;
- resource URLs through the Mezzio router, and safe editor-entered links;
- section summaries rendered through `mezzio/mezzio-template`;
- optional laminas-view helpers (`resource`, `resourceMeta`, `resourceUrl`, `resourceContent`) for
  mezzio-laminasviewrenderer sites.

The entities, repositories, resource manager and metadata builder live in contenir/contenir-resource and are
framework neutral; this package is the Mezzio plumbing around them. It replaces the laminas-mvc parts of
contenir/contenir-resource 1.x; see [Coming from contenir-resource 1.x](docs/migration.md).

## Requirements

- PHP 8.3, 8.4 or 8.5
- contenir/contenir-resource 2.x (with contenir/contenir-db-model 2.x), contenir/contenir-workflow-mezzio 2.1+
- mezzio/mezzio 3.18+, mezzio/mezzio-router, mezzio/mezzio-template
- laminas/laminas-view 2.36+ only for the optional view helpers

## Install

2.0 is a release candidate (`2.0.0-RC1`): contenir-db-model 2 is itself at RC and builds on php-db/phpdb 0.6, which
has no stable release yet. Composer only honours stability flags in the root package, so a site needs these in its
own `composer.json`:

```json
{
    "require": {
        "contenir/contenir-resource-mezzio": "^2.0@RC",
        "contenir/contenir-db-model": "^2.0@RC",
        "php-db/phpdb": "0.6.x-dev@dev"
    }
}
```

Alternatively set `"minimum-stability": "dev"` with `"prefer-stable": true` in the site's `composer.json` and
require `contenir/contenir-resource-mezzio` normally.

With [laminas-component-installer](https://docs.laminas.dev/laminas-component-installer/) the config providers are
added automatically. Otherwise register all of them:

```php
// config/config.php
$aggregator = new ConfigAggregator([
    \PhpDb\ConfigProvider::class,
    \Contenir\Db\Model\ConfigProvider::class,
    \Contenir\Workflow\ConfigProvider::class,
    \Contenir\Resource\Core\ConfigProvider::class,   // repositories, manager, metadata
    \Contenir\Resource\Mezzio\ConfigProvider::class, // middleware, strategy, URLs, view helpers
    // ...
]);
```

## Wiring

### 1. Route resources through a workflow

```php
// config/autoload/resource.global.php
use Contenir\Resource\Mezzio\Workflow\ResourceStrategy;
use Contenir\Workflow\Factory\WorkflowApplicationDelegatorFactory;
use Mezzio\Application;

return [
    'workflow_manager' => [
        'strategy' => ResourceStrategy::class,   // repository defaults to ResourceTreeAdapter
        'cache'    => 'FilesystemCache',
    ],
    'dependencies' => [
        'delegators' => [
            Application::class => [WorkflowApplicationDelegatorFactory::class],
        ],
    ],
    'contenir_resource' => [
        'base_url' => 'https://www.example.com',
    ],
];
```

Each resource is routed by the workflow plugin named in its `workflow` column (`page` when empty). The default
`PageWorkflow` has no handler, so register your own:

```php
use Contenir\Workflow\Workflow\AbstractPageWorkflow;

final class PageWorkflow extends AbstractPageWorkflow
{
    protected ?string $middleware = \App\Handler\PageHandler::class;
}
```

and add it to the `WorkflowPluginManager` (for example as the `page` alias). See [Routing and navigation](docs/routing.md).

### 2. Pipe the middleware after routing

```php
// config/pipeline.php
$app->pipe(RouteMiddleware::class);
$app->pipe(\Contenir\Resource\Mezzio\Middleware\ResourceMiddleware::class);
// ...
$app->pipe(DispatchMiddleware::class);
```

### 3. Use the resource in the handler

```php
use Contenir\Resource\Core\Metadata\MetaTagRenderer;
use Contenir\Resource\Mezzio\ResourceAttribute;

final class PageHandler implements RequestHandlerInterface
{
    public function __construct(
        private TemplateRendererInterface $templates,
        private MetaTagRenderer $meta,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $page = ResourceAttribute::require($request);

        return new HtmlResponse($this->templates->render('app::page', [
            'page' => $page,
            'head' => $this->meta->render(ResourceAttribute::metadata($request)), // output unescaped in <head>
        ]));
    }
}
```

## What is in the package

| Class | Purpose |
| --- | --- |
| `Workflow\ResourceStrategy` | workflow-mezzio strategy: workflow from the `workflow` column; navigation label, visibility and lastmod from the resource |
| `Workflow\ResourceTreeAdapter` | workflow-mezzio resource adapter over `ResourceRepository::findPageTree()` |
| `Workflow\WorkflowResource` | A resource entity presented as a workflow-mezzio `ResourceInterface` |
| `Middleware\ResourceMiddleware` | Resolves the routed resource; 404s missing or unpublished ones; attaches resource and `PageMetadata` |
| `ResourceAttribute` | Reads the attached resource (`resource()`, `require()`) and metadata (`metadata()`) |
| `Url\ResourceUrlGenerator` | Resource links through the router, editor-entered links through `ExternalUrl` |
| `Content\TemplateSectionRenderer` | Renders section content with the template renderer, for `ResourceSummary` |
| `View\Helper\ResourceHelper`, `ResourceMetaHelper`, `ResourceUrlHelper`, `ResourceContentHelper` | Optional laminas-view helpers |
| `ConfigProvider`, `Container\*Factory` | Container wiring |

Every concrete class is `final`. The docs cover each area:

- [Routing and navigation](docs/routing.md)
- [The resource middleware](docs/middleware.md)
- [Metadata, templates and links](docs/templates.md)
- [laminas-view helpers](docs/view-helpers.md)
- [Configuration](docs/configuration.md)
- [Coming from contenir-resource 1.x (laminas-mvc)](docs/migration.md)

## Security

- **Unpublished pages.** The route cache can outlive a change in the admin. The middleware re-checks every routed
  resource and answers missing, pending, inactive or archived ones with the not-found handler; the page handler never
  sees them.
- **Request input.** Only an array `id` route default with a `resourceId` key is treated as a resource route, so a path
  placeholder called `id` can never trigger a lookup; ids other than positive integers never reach the database.
- **Host header.** Set `contenir_resource.base_url` in production; otherwise canonical and Open Graph URLs use the
  request's host, which the client controls. Query strings never reach canonical or share URLs.
- **Links and images.** Editor-entered links and share images are limited to safe schemes; `javascript:`, `data:` and
  the like are dropped.
- **Escaping.** `MetaTagRenderer` and the laminas-view head helpers escape everything; summaries and link pairs are
  plain text that templates must escape.

## Development

The QA toolchain is [php-db/phpdb-qa-tools](https://github.com/php-db/phpdb-qa-tools).
[Mago](https://mago.carthage.software/) is a standalone binary, installed separately (`brew install mago`).

```bash
composer check             # everything below
composer cs-check          # mago format --check && mago lint
composer static-analysis   # mago analyze
composer test              # unit suite: no I/O
composer test-integration  # integration suite: a real Mezzio pipeline, ServiceManager and in-memory SQLite
composer test-coverage     # both suites, clover.xml for Codecov
composer mutation-test     # Infection over both suites (needs Xdebug or PCOV)
```

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).

# CMS previews

With [contenir-preview-mezzio](https://github.com/contenir/contenir-preview-mezzio) as well, a site renders the
Contenir CMS's previews of its pages with the editor's unsaved values, drafts included, with no site code. This package
supplies the resolver that says which middleware shows a page; contenir-resource supplies the values.

## Setting it up

Require the preview package, pipe its middleware before routing and keep `ResourceMiddleware` after it:

```php
$app->pipe(BodyParamsMiddleware::class);
$app->pipe(\Contenir\Preview\Mezzio\PreviewMiddleware::class);
$app->pipe(RouteMiddleware::class);
$app->pipe(\Contenir\Resource\Mezzio\Middleware\ResourceMiddleware::class);
$app->pipe(DispatchMiddleware::class);
```

Then name the resolver for the CMS section that edits the site's pages, in the `contenir_preview` settings the site
shares with the CMS:

```php
use Contenir\Resource\Mezzio\Preview\WorkflowPageResolver;

return [
    'contenir_preview' => [
        'issuer'    => 'https://admin.example.com',
        'audience'  => 'https://www.example.com',
        'sign_with' => '2026-10',
        'resolvers' => [
            'site.page.home' => WorkflowPageResolver::class,
        ],
    ],
];
```

The secret goes in the local, unversioned file, as contenir-preview-mezzio's README describes.

## The resolver

`Preview\WorkflowPageResolver` previews a page at the middleware its workflow (the CMS's Template field) names, with
its id as the `id` route parameter, just as its workflow route carries it. `ResourceMiddleware` then loads the page as on
a visit. The page is dispatched without routing, so a changed slug or template shows as it will once saved. A page not
saved yet has no id and is not previewed, nor is one whose template the site lacks or names no middleware for.

contenir-workflow-mezzio has no workflow for article-like resources, so their sections have no resolver here; a site can
name a path or route resolver for them, or a resolver service of its own.

## The editor's values

The values come from [contenir-resource](https://github.com/contenir/contenir-resource/blob/main/docs/previews.md):
while a preview is rendered, its delegator wraps the `ResourceManagerInterface` service so a lookup that finds the
previewed resource gets it with the editor's values, lists included. `ResourceMiddleware`, the view helpers and the
site's own handlers all use that service, so they see the preview however they load the resource.

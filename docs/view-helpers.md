# laminas-view helpers

For sites on `mezzio/mezzio-laminasviewrenderer`, the `ConfigProvider` registers four helpers under `view_helpers`,
with the names the laminas-mvc 1.x helpers had. laminas-view is a suggested dependency only: the rest of the package
works with any template renderer, and the helpers are thin wrappers over the same services.

The helpers are plain invokable classes, not `AbstractHelper` subclasses (deprecated since laminas-view 2.40), and
receive their dependencies through `ViewHelperFactory`.

| Name | Helper | Does |
| --- | --- | --- |
| `resource` / `Resource` | `ResourceHelper` | `resource($id)` gives the active resource (or `null`); `resource()` gives the helper, with `findBySlug()`, `findByWorkflow()`, `findActivePageByWorkflow()` (all active only) |
| `resourceMeta` / `ResourceMeta` | `ResourceMetaHelper` | `resourceMeta($metadata)` sets `headTitle` (replacing it, when there is a title), the canonical `headLink` and the `headMeta` tags |
| `resourceUrl` / `ResourceUrl` | `ResourceUrlHelper` | `[$url, $target] = resourceUrl($resource, $url, $target)` |
| `resourceContent` / `ResourceContent` | `ResourceContentHelper` | `resourceContent($resourceOrText)`, a plain-text summary |

`resourceMeta` takes the `PageMetadata` the middleware attached, not the entity, because the canonical URL needs the
request. Pass it from the handler:

```php
return new HtmlResponse($this->templates->render('app::page', [
    'page'     => ResourceAttribute::require($request),
    'metadata' => ResourceAttribute::metadata($request),
]));
```

```php
<?php $this->resourceMeta($this->metadata) ?>
```

The Open Graph tags use the `property` attribute, which laminas-view's `headMeta` only accepts with an HTML5 or RDFa
doctype: set `$this->doctype('HTML5')` in the layout. Escape the output of `resourceUrl` and `resourceContent`.

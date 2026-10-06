# Coming from contenir-resource 1.x (laminas-mvc)

The laminas-mvc package was `contenir/contenir-resource` 1.x (on contenir-db-model 1.x and contenir-mvc-workflow
1.x). In 2.x it is split: the framework-neutral core stays `contenir/contenir-resource` 2.x (its
[UPGRADE.md](https://github.com/contenir/contenir-resource/blob/main/UPGRADE.md) covers entities, repositories and
the resource manager), and this package replaces the laminas-mvc parts for Mezzio.

## Feature map

| laminas-mvc 1.x | Mezzio 2.x |
| --- | --- |
| `Module::getConfig()` | `Contenir\Resource\Core\ConfigProvider` + `Contenir\Resource\Mezzio\ConfigProvider` |
| `resource.repository.*` config (repository services) | `contenir_resource.resource_entity` etc. (entity classes) |
| `service_manager` aliases `resource`, `resource_collection`, `resource_type` | Class names (`ResourceRepository::class`, ...) |
| `BaseResourceEntity` (implements mvc-workflow `ResourceInterface`) | `AbstractResourceEntity` (framework neutral) wrapped by `Workflow\WorkflowResource` for workflow-mezzio |
| `BaseResourceRepository::getWorkflowResources()` | `Workflow\ResourceTreeAdapter` (over `ResourceRepository::findPageTree()`) as `workflow_manager.repository` |
| contenir-mvc-workflow `ResourceStrategy` (workflow from `$resource->workflow`, label `title_short ?? title`, lastmod) | `Workflow\ResourceStrategy` |
| Controller routes `<type>-<id>` with child routes (`getRouteId($path)`) | Mezzio routes `<type>-<id>`; Mezzio has no child routes |
| Controller plugin `$this->resource()` (the `ResourceManager`) | Inject `ResourceManagerInterface` into the handler |
| `$this->resource($id)` (throws `MissingResourceException`) | `ResourceMiddleware` resolves the routed resource (404 when missing or unpublished); `ResourceAttribute::require($request)` in the handler |
| `$this->resource()->findCollectionByType('product')` | `ResourceManagerInterface::findCollectionByType('product')` |
| View helper `resource($id)`, `->findBySlug()`, `->findByWorkflow()`, `->findActivePageByWorkflow()` | `ResourceManagerInterface::findActive*()`, or the `resource` laminas-view helper |
| View helper `resourceMeta($resource)` (HeadTitle, HeadMeta, HeadLink) | `PageMetadata` on the request + `MetaTagRenderer` (any renderer), or the `resourceMeta($metadata)` laminas-view helper |
| `ResourceMeta::getText()`, `getKeywords()` | `MetaText::summarise()`, `MetaText::keywords()` |
| View helper `resourceUrl($resource, $url, $target)` → `[$url, $target]` | `ResourceUrlGenerator::generate()` → `ResourceLink` (`toArray()` gives the pair), or the `resourceUrl` laminas-view helper |
| `UrlFormat` for external links | `ExternalUrl::normalise()` (scheme allow-list) |
| View helper `resourceContent($resource)` with the `application/component/_section` partial | `ResourceSummary` + `TemplateSectionRenderer` (`section_template`), or the `resourceContent` laminas-view helper |
| `RichContent` filter on meta descriptions | Not applicable: site-specific; descriptions are reduced to plain text |
| `Asset` helper for `og:image` | `ImageUrlResolverInterface` (default `PathImageUrlResolver` with `image_base_url`) |
| Navigation `resource` key (ACL resource `controller:...`) | Not applicable: Mezzio has no controller ACL resources; use route names |
| mvc-workflow landing pages and sub-pages (`getRoutePages()`) | Not applicable: workflow-mezzio has no sub-page routes |
| `Exception\MissingResourceException` (laminas-mvc `ExceptionInterface`) | `Contenir\Resource\Core\Exception\MissingResourceException` |

## Templates

```php
// 1.x (laminas-mvc view)
<?php $this->ResourceMeta($this->page) ?>

// 2.x with mezzio-laminasviewrenderer: the handler passes 'metadata' => ResourceAttribute::metadata($request)
<?php $this->ResourceMeta($this->metadata) ?>
```

```php
// 1.x
[$url, $target] = $this->ResourceUrl($item->resource_id, $item->url);

// 2.x: the same with the laminas-view helper
[$url, $target] = $this->resourceUrl($item->resourceId, $item->url);
```

## Behaviour changes

- Pages that are not active are not served, even when an old route cache still has their route.
- `getMetaPublish()` is the `created` date (1.x returned `updated`), so `og:updated_time` changes for pages edited
  after creation.
- `og:updated_time` and sitemap `lastmod` are ISO 8601.
- `og:url` is the canonical URL without the query string, on `base_url` when configured.
- Hidden pages stay in the navigation tree with `visible: false`, and their children are routed. In 1.x hidden pages
  and their children were left out of navigation, and the children got no routes.
- External links without a scheme get `https://` (1.x `http://`); unsafe schemes are dropped.

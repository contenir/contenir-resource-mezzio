# Routing and navigation

Routes and navigation come from contenir-workflow-mezzio. This package supplies its two extension points for
Contenir resources.

## ResourceTreeAdapter

The `workflow_manager.repository` service (and the default of this package's strategy factory). It returns
`ResourceRepository::findPageTree()`, the active top-level `page` resources and their active descendants, wrapped as
`WorkflowResource`s. Inactive resources, and everything below them, get no route.

## ResourceStrategy

Set `workflow_manager.strategy` to `Contenir\Resource\Mezzio\Workflow\ResourceStrategy`. Its factory reads the same
`workflow_manager` keys as workflow-mezzio's own: `repository` (default `ResourceTreeAdapter`), `cache` (default
`FilesystemCache`) and `cache_key` (default `WorkflowResourceCache`).

For each resource:

- **Workflow:** the plugin named in the `workflow` column, or `page` when it is empty. Register your workflows with
  the `WorkflowPluginManager`; a workflow whose middleware is not set (the default `PageWorkflow`) gives the resource
  a navigation page but no route.
- **Route:** as workflow-mezzio builds it: name `<type>-<id>` (`AbstractResourceEntity::getRouteName()`), path from the
  slug, and the `id` default `['resourceId' => <id>]`, which `ResourceMiddleware` resolves.
- **Navigation page:** label from the short title (or title, or the workflow's label), `visible` from the `visible`
  column (null is hidden), `lastmod` from the `updated` date in ISO 8601.

Hidden resources keep their route and stay in the navigation tree with `visible: false`, so menus and sitemaps that
honour `visible` leave them out while their children are still routed.

## Generating URLs

`Url\ResourceUrlGenerator::generate($resource, $url, $target)` returns a `ResourceLink`:

| Call | url | target |
| --- | --- | --- |
| `generate($entity)` | the entity's route URL | `$target` |
| `generate(5)` / `generate('5')` | the route URL of active resource 5 | `$target` |
| `generate(url: 'example.com')` | `https://example.com` (see `ExternalUrl`) | `_blank` |
| `generate()` | `null` | `$target` |

A resource that is not found, inactive, unsaved or has no route gives `url: null`, so a template can render plain
text instead of a broken link.

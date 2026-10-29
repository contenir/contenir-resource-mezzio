# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- The page metadata builder takes the site's URL from its Site Settings as its base URL when
  `contenir_resource.base_url` is not set: `site.url`, with the CMS's `settings` overlay laid over it. Canonical
  and share addresses then never take the request's host on a site that names its address, without any config
  of its own. A `base_url` set in `contenir_resource` still wins. (`Container\SiteBaseUrlDelegator`, registered
  on `PageMetadataBuilder`.)

### Changed

- Requires `contenir/contenir-resource` ^2.0.0-RC4, whose page metadata writes the Twitter tags as
  `<meta name="twitter:…">` with a `twitter:card`; the integration tests expect them so.

- `Preview\WorkflowPageResolver` shows a preview that carries no template by the stored page's workflow, whatever the
  page's status, rather than by `page`. A shared preview link carries no values, so this is what places it at the
  page's own template. The resolver takes the `ResourceManagerInterface` as its second argument; its factory passes it.

## [2.0.0-RC2] - 2026-10-18

### Added

- CMS previews of pages, with contenir-preview-mezzio piped before routing: `Preview\WorkflowPageResolver` resolves
  the CMS section that edits pages to the middleware its workflow names, and `ResourceMiddleware` loads the page with
  the editor's unsaved values, drafts included, through contenir-resource's preview of the resource manager. See
  [CMS previews](docs/previews.md).

## [2.0.0-RC1] - 2026-10-06

First release: the Mezzio adapter for contenir/contenir-resource 2, replacing the laminas-mvc parts of
contenir/contenir-resource 1.x. See [Coming from contenir-resource 1.x](docs/migration.md).

### Added

- `Workflow\ResourceStrategy`, `Workflow\ResourceTreeAdapter` and `Workflow\WorkflowResource`: workflow-mezzio
  routes and navigation from the resource tree.
- `Middleware\ResourceMiddleware` and `ResourceAttribute`: the routed resource and its page metadata on the request;
  missing or unpublished resources answered by the not-found handler.
- `Url\ResourceUrlGenerator`: resource links through the Mezzio router, safe editor-entered links.
- `Content\TemplateSectionRenderer`: section summaries rendered with mezzio-template.
- Optional laminas-view helpers `resource`, `resourceMeta`, `resourceUrl` and `resourceContent`.
- `ConfigProvider` and factories.
- Mago, PHPUnit unit and integration suites (a real Mezzio pipeline over in-memory SQLite), Infection (MSI 100%) and
  Codecov in CI.

# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

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

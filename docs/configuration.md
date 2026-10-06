# Configuration

## Services

`Contenir\Resource\Mezzio\ConfigProvider` registers:

| Service | Factory |
| --- | --- |
| `Middleware\ResourceMiddleware` | `Container\ResourceMiddlewareFactory` |
| `Workflow\ResourceStrategy` | `Container\ResourceStrategyFactory` |
| `Workflow\ResourceTreeAdapter` | `Container\ResourceTreeAdapterFactory` |
| `Url\ResourceUrlGenerator` | `Container\ResourceUrlGeneratorFactory` |
| `Content\TemplateSectionRenderer` | `Container\TemplateSectionRendererFactory` |

and the view helpers under `view_helpers` (see [view helpers](view-helpers.md)). The repositories, resource
manager, metadata builder, image resolver, summary and tag renderer come from
`Contenir\Resource\Core\ConfigProvider`; see contenir-resource's
[configuration](https://github.com/contenir/contenir-resource/blob/main/docs/configuration.md).

## Keys

`contenir_resource` (in addition to contenir-resource's own keys: entity classes, `base_url`, `image_base_url`,
`description_length`, `summary_length`, `section_renderer`):

| Key | Default | Used by |
| --- | --- | --- |
| `not_found_handler` | `Mezzio\Handler\NotFoundHandler` | `ResourceMiddleware`; a `RequestHandlerInterface` service |
| `section_template` | none (required by `TemplateSectionRenderer`) | `TemplateSectionRenderer` |

`workflow_manager` (shared with contenir-workflow-mezzio):

| Key | Default | Used by |
| --- | --- | --- |
| `strategy` | none: set it to `ResourceStrategy::class` | workflow-mezzio's delegator and middleware |
| `repository` | `ResourceTreeAdapter` | `ResourceStrategyFactory` |
| `cache` | `FilesystemCache` | `ResourceStrategyFactory`; a laminas-cache `StorageInterface` service |
| `cache_key` | `WorkflowResourceCache` | `ResourceStrategyFactory` |

Values of the wrong type, or services of the wrong type, throw `ConfigurationException` naming the key or service.

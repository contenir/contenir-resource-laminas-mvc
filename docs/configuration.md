# Configuration

## Services

`Module::getConfig()` returns `ConfigProvider`'s configuration. Under `service_manager` it registers
contenir-resource's own services (repositories, `ResourceManager` with its `ResourceManagerInterface` alias,
`PageMetadataBuilder`, `PathImageUrlResolver`, `ResourceSummary`, `MetaTagRenderer`), which that package registers
under `dependencies` only, and adds:

| Service | Factory |
| --- | --- |
| `Listener\ResourceListener` | `Container\ResourceListenerFactory` |
| `Workflow\ResourceTreeAdapter` | `Container\ResourceTreeAdapterFactory` |
| `Url\ResourceUrlGenerator` | `Container\ResourceUrlGeneratorFactory` (uses the `router` service) |
| `Content\PartialSectionRenderer` | `Container\PartialSectionRendererFactory` (uses the `ViewRenderer` service) |

Under `controller_plugins` it registers `resource` (see [controllers](controllers.md)), and under `view_helpers` the
four helpers (see [view helpers](view-helpers.md)). The `EntityManager` comes from contenir-db-model's module.

## Keys

`contenir_resource`, in addition to contenir-resource's own keys (entity classes, `base_url`, `image_base_url`,
`description_length`, `summary_length`; see its
[configuration](https://github.com/contenir/contenir-resource/blob/main/docs/configuration.md)):

| Key | Default | Used by |
| --- | --- | --- |
| `section_renderer` | `Content\PartialSectionRenderer` (set by this module) | `ResourceSummary`; set it to `null` to summarise descriptions only |
| `section_template` | `application/component/_section` | `PartialSectionRenderer` |

`workflow_manager` belongs to contenir-workflow-laminas-mvc; this package contributes none of it. Set
`strategy.repository` to `Workflow\ResourceTreeAdapter` (see [routing](routing.md)).

Values of the wrong type, or services of the wrong type, throw contenir-resource's `ConfigurationException` naming
the key or service.

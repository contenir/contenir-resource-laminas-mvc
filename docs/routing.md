# Routing and navigation

Routes and navigation come from contenir-workflow-laminas-mvc. This package supplies the resource adapter its strategy
reads, and presents each resource the way workflow-laminas-mvc expects.

## ResourceTreeAdapter

Set `workflow_manager.strategy.repository` to `Contenir\Resource\Laminas\Mvc\Workflow\ResourceTreeAdapter`. It returns
`ResourceRepository::findPageTree()`, the active top-level `page` resources and their active descendants, wrapped as
`WorkflowResource`s. Inactive resources, and everything below them, get no route.

## The strategy

workflow-laminas-mvc's own strategy needs no subclass: set `workflow_manager.strategy.type` to
`Contenir\Mvc\Workflow\Strategy\ResourceStrategyInterface` (or your own `AbstractResourceStrategy` subclass,
registered with workflow-laminas-mvc's `ResourceStrategyFactory`). Cache the tree with
`workflow_manager.strategy.options.cache`, a laminas-cache storage service name. See workflow-laminas-mvc's
[configuration](https://github.com/contenir/contenir-workflow-laminas-mvc/blob/main/docs/configuration.md).

## WorkflowResource

workflow-laminas-mvc reads a resource's 1.x column names as properties. `WorkflowResource` answers them from the
framework-neutral entity:

| Property | Value |
| --- | --- |
| `workflow` | `getWorkflowName()`: the `workflow` column, or `page` when it is empty |
| `title_short` | `getNavigationLabel()`: the short title, or else the title |
| `title` | the title, or nothing when it is blank |
| `visible` | `isVisible()`: the `visible` column (null is hidden) |
| `children` | the attached children, as `WorkflowResource`s |
| `resource_type_id`, `resource_id` | the type and id, which make the route name `<type>-<id>` |

It also implements contenir-metadata's `MetadataInterface`, so the navigation `lastmod` is the entity's `updated`
date. Workflows reach the entity with `getEntity()`.

For each resource the strategy then builds, as workflow-laminas-mvc does:

- **Route:** name `<type>-<id>` (the same as `AbstractResourceEntity::getRouteName()`), path from the slug, and the
  `resource_id` default `['resourceId' => <id>]`, which `ResourceListener` resolves.
- **Navigation page:** label from the short title (or title), `visible` from the resource, `lastmod` as
  `Y-m-d H:i:s`. Hidden resources are routed but left out of navigation, and their children get no route, as in 1.x.

A workflow without a controller (the bundled `PageWorkflow`) cannot build a route: register your own workflows, as in
the [README](../README.md#1-route-resources-through-a-workflow).

## Generating URLs

`Url\ResourceUrlGenerator::generate($resource, $url, $target)` returns a `ResourceLink`:

| Call | url | target |
| --- | --- | --- |
| `generate($entity)` | the entity's route URL | `$target` |
| `generate(5)` / `generate('5')` | the route URL of active resource 5 | `$target` |
| `generate(url: 'example.com')` | `https://example.com` (see `ExternalUrl`) | `_blank` |
| `generate()` | `null` | `$target` |

A resource that is not found, inactive, unsaved or has no route gives `url: null`, so a template can render plain
text instead of a broken link. URLs are assembled by the `router` service.

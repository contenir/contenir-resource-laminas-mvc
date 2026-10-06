# Coming from contenir-resource 1.x

contenir-resource 1.x (on contenir-db-model 1.x and contenir-mvc-workflow 1.x) was a laminas-mvc module. In 2.x it is
split: the framework-neutral core stays `contenir/contenir-resource` 2.x (its
[UPGRADE.md](https://github.com/contenir/contenir-resource/blob/main/UPGRADE.md) covers entities, repositories and
the resource manager), and this package carries the laminas-mvc parts. contenir-mvc-workflow is now
`contenir/contenir-workflow-laminas-mvc`.

## Install

```diff
 "require": {
-    "contenir/contenir-resource": "^1.1"
+    "contenir/contenir-resource-laminas-mvc": "^2.0@RC",
+    "contenir/contenir-resource": "^2.0@RC",
+    "contenir/contenir-db-model": "^2.0@RC",
+    "php-db/phpdb": "0.6.x-dev@dev"
 }
```

```diff
 // config/modules.config.php
-    'Contenir\Resource',
+    'Contenir\Resource\Laminas\Mvc',
```

## Feature map

| 1.x | 2.x |
| --- | --- |
| `Contenir\Resource\Module::getConfig()` | `Contenir\Resource\Laminas\Mvc\Module` (config from `ConfigProvider`, including the core's services) |
| `resource.repository.resource`, `.resource_collection`, `.resource_type` (repository service names) | `contenir_resource.resource_entity`, `resource_collection_entity`, `resource_type_entity` (entity classes); the repositories are `ResourceRepository`, `ResourceCollectionRepository`, `ResourceTypeRepository` |
| `service_manager` aliases `resource`, `resource_collection`, `resource_type` | Not applicable: use the class names (`ResourceRepository::class`, ...) |
| `ResourceManager`, `ResourceManagerFactory` | `Contenir\Resource\Core\ResourceManager` behind `ResourceManagerInterface` (core) |
| `ResourceManager::findOne($id)`, `findOneByField()`, `findByField()` | `find($id)`, `findActive($id)`, `findOneBy()`, `findBy()`, with entity property names (`resourceTypeId`, not `resource_type_id`) |
| `ResourceManager::findByType()`, `findCollectionByType()`, `findActivePageByWorkflow()` | The same names on `ResourceManagerInterface` |
| `ResourceManager::__call()` magic finders (`findActiveArticleBySlug(...)`) | Not applicable: replaced by explicit criteria, e.g. `findOneByType('article', ['slug' => $slug, 'status' => ResourceStatus::Active])`; caller-supplied property names are validated by contenir-db-model |
| `Model\Entity\BaseResourceEntity` (implements mvc-workflow `ResourceInterface`) | `AbstractResourceEntity` / `ResourceEntity` (framework neutral), wrapped by `Workflow\WorkflowResource` for workflow-laminas-mvc |
| `BaseResourceEntity::getRouteId($path)`, `getRoutePath()` | `AbstractResourceEntity::getRouteName()`; the path is built by the workflow from `getSlug()` |
| `BaseResourceCollectionEntity`, `BaseResourceTypeEntity` and their repositories | `AbstractResourceCollectionEntity`, `AbstractResourceTypeEntity` and the core repositories |
| `BaseResourceRepository::getWorkflowResources()` (mvc-workflow `ResourceAdapterInterface`) | `Workflow\ResourceTreeAdapter`, as `workflow_manager.strategy.repository` |
| contenir-mvc-workflow strategy over 1.x entities | workflow-laminas-mvc's own `ResourceStrategy`, unchanged: `WorkflowResource` answers the `workflow`, `title`, `title_short`, `visible`, `children`, `resource_type_id` and `resource_id` properties it reads |
| Controller plugin `$this->resource()` (the `ResourceManager`) | `$this->resource()` (the `ResourceManagerInterface`) |
| `$this->resource($id)` / `$this->resource($id, false)` | The same; also accepts the `resource_id` route parameter. Only active resources |
| `ResourcePlugin::handleResult()` | Not applicable: internal to the plugin |
| Looking up the routed resource in each controller | `Listener\ResourceListener` resolves it (404 when missing or unpublished); `$this->plugin('resource')->routed()` or `ResourceParam::require($this->getEvent())` |
| View helper `resource($id)`, `->findBySlug()`, `->findByWorkflow()`, `->findActivePageByWorkflow()` | The `resource` helper, the same calls |
| View helper `resourceMeta($resource)` (`HeadTitle`, `HeadMeta`, `HeadLink`, `ServerUrl`) | The `resourceMeta` helper, the same call, built on `PageMetadataBuilder`; it also takes a `PageMetadata` |
| `ResourceMeta::getText()`, `getKeywords()`, `$banned_words`, `$min_word_length` | `MetaText::summarise()`, `MetaText::keywords()` (core) |
| `RichContent` view helper on meta descriptions | Not applicable: site-specific; descriptions are reduced to plain text |
| `Asset` view helper for `og:image` | `ImageUrlResolverInterface` (default `PathImageUrlResolver` with `image_base_url`); alias it to your asset service's resolver |
| View helper `resourceUrl($resource, $url, $target)` → `[$url, $target]` | The `resourceUrl` helper, the same call, over `Url\ResourceUrlGenerator` (`ResourceLink`) |
| `UrlFormat` view helper for external links | `ExternalUrl::normalise()` (scheme allow-list) |
| View helper `resourceContent($resource)` with the `application/component/_section` partial | The `resourceContent` helper over `ResourceSummary` + `Content\PartialSectionRenderer`, same default partial (`contenir_resource.section_template`) |
| `Exception\MissingResourceException` (laminas-mvc `ExceptionInterface`) | `Contenir\Resource\Core\Exception\MissingResourceException` (core `ExceptionInterface`) |
| `laminas/laminas-filter` dependency | Not applicable: no longer used |

## Templates and controllers

Templates keep working with property names updated to the 2.x entities:

```php
// 1.x
[$url, $target] = $this->ResourceUrl($item->resource_id, $item->url);

// 2.x
[$url, $target] = $this->resourceUrl($item->resourceId, $item->url);
```

```php
// 1.x
$page = $this->resource($this->params()->fromRoute('resource_id'));

// 2.x: either still works, the second does no extra query
$page = $this->resource($this->params()->fromRoute('resource_id'));
$page = $this->plugin('resource')->routed();
```

## Behaviour changes

- Pages that are not active are not served, even when an old route cache still has their route: the listener
  answers them with a 404. `$this->resource($id)` and the `resource` helper find active resources only (the 1.x
  plugin found any status).
- `getMetaPublish()` is the `created` date (1.x returned `updated`), so `og:updated_time` changes for pages edited
  after creation.
- `og:updated_time` is ISO 8601 (1.x `Y-m-d H:i:s`). The navigation and sitemap `lastmod` keeps workflow-laminas-mvc's
  `Y-m-d H:i:s`.
- `og:url` and `twitter:url` are the canonical URL without the query string (1.x kept it), on `base_url` when
  configured.
- The meta description is `meta_description` or else `description`, reduced to plain text without the `RichContent`
  filter; blank titles and descriptions emit no tag.
- External links without a scheme get `https://` (1.x `http://`); unsafe schemes are dropped. Share images with a
  scheme other than http or https are dropped.
- Hidden pages are still routed and left out of navigation, and their children still get no route, as in 1.x (this
  differs from the Mezzio adapter, where workflow-mezzio keeps hidden pages in the tree).
- Route defaults carry `resource_id => ['resourceId' => <id>]` (1.x `['resource_id' => <id>]`), the core entity's
  primary key name.

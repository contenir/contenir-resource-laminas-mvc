# The route listener and the controller plugin

## ResourceListener

`Module::onBootstrap()` attaches `Listener\ResourceListener` to the application's route event at priority -100, after
laminas-mvc's router. For each request:

1. If the `resource_id` route parameter is not an array with a `resourceId` key, the request is not a resource route
   and passes through untouched. A path segment named `resource_id` is always a string, so it never qualifies.
2. The resource is looked up with `ResourceManagerInterface::findActive()`. Only positive integer ids (or digit
   strings) are queried.
3. A missing or non-active resource is answered as an unmatched route: the event gets the
   `Application::ERROR_ROUTER_NO_MATCH` error and the dispatch error event is triggered, so laminas-mvc's
   `RouteNotFoundStrategy` renders the 404 page. This covers pages unpublished after the route cache was built.
4. Otherwise the `MvcEvent` carries the resource under `AbstractResourceEntity::class` and its `PageMetadata` (built
   from the request URL and `base_url`) under `PageMetadata::class`.

Read them with `ResourceParam`:

```php
ResourceParam::resource($event); // ?AbstractResourceEntity
ResourceParam::require($event);  // AbstractResourceEntity, or MissingResourceException
ResourceParam::metadata($event); // ?PageMetadata
```

## The resource() plugin

`Controller\Plugin\ResourcePlugin` is registered as `resource` (and `Resource`), as in 1.x:

| Call | Returns |
| --- | --- |
| `$this->resource()` | the `ResourceManagerInterface` |
| `$this->resource($id)` | the active resource with that id, or `MissingResourceException('Resource not found')` |
| `$this->resource($id, false)` | the active resource, or `null` |
| `$this->resource($this->params()->fromRoute('resource_id'))` | the same, from the route's primary keys |
| `$this->plugin('resource')->routed()` | the resource `ResourceListener` resolved, or `MissingResourceException` |
| `$this->plugin('resource')->metadata()` | its `PageMetadata`, or `null` |

`routed()` failing means the controller is reached by a route that is not a resource workflow route, or the module
is not loaded; it is a wiring error, so it throws rather than returning a 404.

The resource manager's finders take entity property names (`resourceTypeId`, not `resource_type_id`); see
contenir-resource's [repositories](https://github.com/contenir/contenir-resource/blob/main/docs/repositories.md).

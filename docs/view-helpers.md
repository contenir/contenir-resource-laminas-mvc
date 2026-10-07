# View helpers

The module registers four helpers, with the names the 1.x helpers had. They are plain invokable classes, not
`AbstractHelper` subclasses (deprecated since laminas-view 2.40), and receive their dependencies through
`Container\ViewHelperFactory`.

| Name | Helper | Does |
| --- | --- | --- |
| `resource` / `Resource` | `ResourceHelper` | `resource($id)` gives the active resource (or `null`), and `resource($ids)` the first active one of a list (see below); `resource()` gives the helper, with `findBySlug()`, `findByWorkflow()`, `findActivePageByWorkflow()` (all active only) |
| `resourceMeta` / `ResourceMeta` | `ResourceMetaHelper` | `resourceMeta($resource)` or `resourceMeta($metadata)` sets `headTitle` (replacing it, when there is a title), the canonical `headLink` and the `headMeta` tags |
| `resourceUrl` / `ResourceUrl` | `ResourceUrlHelper` | `[$url, $target] = resourceUrl($resource, $url, $target)` |
| `resourceContent` / `ResourceContent` | `ResourceContentHelper` | `resourceContent($resourceOrText)`, a plain-text summary |

## resource

`resource($id)` takes an int or a string of digits (`'007'` is id 7) and gives the active resource with that id, or
`null`. A blank string gives `null` without a query.

`resource($ids)` takes any iterable of ids, as CMS sections store a linked resource from a multi-select, and gives the
active resource with the lowest of those ids, or `null`, in one query: the resource 1.x's `resource_id IN (...)` lookup
found. List order does not matter, blank entries are skipped, and an empty list gives `null` without a query.

```php
<?php $resource = $this->resource($this->section->resource) ?>
```

Anything else, as the id or as a list entry (a word such as a slug, `'12abc'`, `'-1'`, `'1.5'`, a float, a bool,
`null` in a list, a nested list or an object), throws `Exception\InvalidResourceIdException`, an
`InvalidArgumentException` that implements contenir-resource's `ExceptionInterface`. Use `findBySlug()` for slugs.

## resourceMeta

Pass the resource, as in 1.x; its `PageMetadata` is built for the current request (the `Request` service) on
`contenir_resource.base_url`. The `PageMetadata` the listener built works too (`$this->plugin('resource')->metadata()`
in the controller).

```php
<?php $this->resourceMeta($this->page) ?>
```

The tags, in 1.x order: `og:type`, `og:url`, `twitter:url`, `og:title`, `twitter:title`, `description`,
`og:description`, `twitter:description`, `og:image`, `twitter:image`, `og:updated_time`. laminas-view's head helpers
escape every value. The Open Graph tags use the `property` attribute, which `headMeta` only accepts with an HTML5 or
RDFa doctype: set `view_manager.doctype` to `HTML5`.

## resourceContent

A summary of the resource's description, or else of its section content rendered by `Content\PartialSectionRenderer`
with the `application/component/_section` partial (the 1.x template; change it with
`contenir_resource.section_template`). The partial receives `section` and `resource`. Only entities implementing
`SectionAwareInterface` have sections. The summary is plain text: escape it.

## resourceUrl

The `[url, target]` pair of `ResourceUrlGenerator::generate()` (see [routing](routing.md#generating-urls)). External
links are normalised by `ExternalUrl`: only http, https, mailto and tel are allowed, a bare host gets `https://`, and
anything else gives no URL. Escape both values.

# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [2.0.0-RC2] - Unreleased

### Fixed

- The `resource` view helper accepts a list of resource ids again, as 1.x did and as CMS section templates pass them
  (`$this->resource($this->section->resource)`): it gives the active resource with the lowest id in the list, in one
  query, or `null`. Any iterable is accepted; blank entries are skipped and an empty list finds nothing without a
  query. Strings of digits with leading zeros find the resource, as in 1.x. Ids that are neither an int, a blank
  string nor a string of digits throw the new `Exception\InvalidResourceIdException` instead of finding nothing.
  See [view helpers](docs/view-helpers.md#resource) and [migration](docs/migration.md#behaviour-changes).

## [2.0.0-RC1] - Unreleased

First release: the laminas-mvc adapter for contenir/contenir-resource 2, replacing the laminas-mvc parts of
contenir/contenir-resource 1.x. See [Coming from contenir-resource 1.x](docs/migration.md).

### Added

- `Module` and `ConfigProvider`, registering contenir-resource's services for laminas-mvc.
- `Listener\ResourceListener` and `ResourceParam`: the routed resource and its page metadata on the `MvcEvent`;
  missing or unpublished resources answered with a 404.
- The `resource()` controller plugin (`Controller\Plugin\ResourcePlugin`).
- `Workflow\ResourceTreeAdapter` and `Workflow\WorkflowResource`: workflow-laminas-mvc routes and navigation from the
  resource tree.
- `Url\ResourceUrlGenerator`: resource links through the laminas-mvc router, safe editor-entered links.
- `Content\PartialSectionRenderer`: section summaries rendered with a view partial.
- The view helpers `resource`, `resourceMeta`, `resourceUrl` and `resourceContent`.
- Mago, PHPUnit unit and integration suites (a real laminas-mvc Application over in-memory SQLite), Infection
  (MSI 100%) and Codecov in CI.

### Fixed

- The `resource` view helper accepts a list of ids, as section link fields store them, as 1.x did; it returns the
  first active resource instead of throwing a `TypeError`.

### Notes

- Resolves contenir/contenir-db-model v2.0.0-rc3 or later, the first release that accepts psr/simple-cache 1.x
  alongside laminas-cache 3, which contenir-workflow-laminas-mvc requires.
- Conflicts with laminas/laminas-stdlib below 3.21, whose `SplPriorityQueue` raises deprecations on PHP 8.5.

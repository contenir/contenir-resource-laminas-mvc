# contenir/contenir-resource-laminas-mvc

[![Continuous Integration](https://github.com/contenir/contenir-resource-laminas-mvc/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-resource-laminas-mvc/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/contenir/contenir-resource-laminas-mvc/graph/badge.svg)](https://codecov.io/gh/contenir/contenir-resource-laminas-mvc)

The [laminas-mvc](https://docs.laminas.dev/laminas-mvc/) adapter for
[contenir/contenir-resource](https://github.com/contenir/contenir-resource) 2, so a laminas-mvc site can serve
Contenir resources (pages, articles, collections):

- routes and navigation built from the resource tree through
  [contenir-workflow-laminas-mvc](https://github.com/contenir/contenir-workflow-laminas-mvc);
- a route listener that resolves the routed resource, answers unpublished ones with a 404, and sets the resource and
  its page metadata on the `MvcEvent`;
- the `resource()` controller plugin;
- the `resource`, `resourceMeta`, `resourceUrl` and `resourceContent` view helpers, with the names they had in 1.x.

The entities, repositories, resource manager and metadata builder live in contenir/contenir-resource and are
framework neutral; this package is the laminas-mvc plumbing around them. It replaces the laminas-mvc parts of
contenir/contenir-resource 1.x; see [Coming from contenir-resource 1.x](docs/migration.md). The Mezzio counterpart is
[contenir-resource-mezzio](https://github.com/contenir/contenir-resource-mezzio).

## Requirements

- PHP 8.3, 8.4 or 8.5
- contenir/contenir-resource 2.x with contenir/contenir-db-model 2.x
- contenir/contenir-workflow-laminas-mvc 2.1+
- laminas/laminas-mvc 3.8+, laminas/laminas-router, laminas/laminas-view 2.36+

## Install

2.0 is a release candidate (`2.0.0-RC1`): contenir-resource and contenir-db-model 2 are themselves at RC, and
contenir-db-model builds on php-db/phpdb 0.6, which has no stable release yet. Composer only honours stability flags in
the root package, so a site needs these in its own `composer.json`:

```json
{
    "require": {
        "contenir/contenir-resource-laminas-mvc": "^2.0@RC",
        "contenir/contenir-resource": "^2.0@RC",
        "contenir/contenir-db-model": "^2.0@RC",
        "php-db/phpdb": "0.6.x-dev@dev"
    }
}
```

Alternatively set `"minimum-stability": "dev"` with `"prefer-stable": true` in the site's `composer.json` and
require `contenir/contenir-resource-laminas-mvc` normally.

Then add the modules, in this order, to `config/modules.config.php`:

```php
return [
    'Laminas\Router',
    // ...
    'Contenir\Db\Model',
    'Contenir\Mvc\Workflow',
    'Contenir\Resource\Laminas\Mvc', // also registers contenir-resource's own services
    'Application',
];
```

php-db/phpdb has no module: register the database adapter (`PhpDb\Adapter\AdapterInterface`) in your own
`service_manager` config, as contenir-db-model's README describes.

## Wiring

### 1. Route resources through a workflow

```php
// config/autoload/resource.global.php
use Contenir\Mvc\Workflow\Strategy\ResourceStrategyInterface;
use Contenir\Mvc\Workflow\Workflow\WorkflowFactory;
use Contenir\Resource\Laminas\Mvc\Workflow\ResourceTreeAdapter;

return [
    'workflow_manager'  => [
        'aliases'   => ['page' => App\Workflow\PageWorkflow::class],
        'factories' => [App\Workflow\PageWorkflow::class => WorkflowFactory::class],
        'strategy'  => [
            'type'       => ResourceStrategyInterface::class,
            'repository' => ResourceTreeAdapter::class,
            'options'    => ['cache' => 'DataCache'],
        ],
    ],
    'contenir_resource' => [
        'base_url' => 'https://www.example.com',
    ],
];
```

Each resource is routed by the workflow plugin named in its `workflow` column (`page` when empty). Workflows name the
controller:

```php
use Contenir\Mvc\Workflow\Workflow\AbstractPageWorkflow;

final class PageWorkflow extends AbstractPageWorkflow
{
    protected ?string $controller = \Application\Controller\PageController::class;
}
```

See [Routing and navigation](docs/routing.md).

### 2. Use the resource in the controller

The module attaches `Listener\ResourceListener` to the route event, so by the time the controller runs the routed
resource is resolved and active:

```php
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

final class PageController extends AbstractActionController
{
    public function indexAction(): ViewModel
    {
        return new ViewModel([
            'page' => $this->plugin('resource')->routed(),
        ]);
    }
}
```

The 1.x idiom still works too: `$this->resource($this->params()->fromRoute('resource_id'))`.

### 3. Use the view helpers

```php
<?php $this->resourceMeta($this->page) ?>
<h1><?= $this->escapeHtml($this->page->title) ?></h1>
<p><?= $this->escapeHtml($this->resourceContent($this->page)) ?></p>
<?php [$url, $target] = $this->resourceUrl($item->resourceId, $item->url) ?>
```

Set `$this->doctype('HTML5')` (or `view_manager.doctype`) so `headMeta` accepts the Open Graph `property` tags.

## What is in the package

| Class | Purpose |
| --- | --- |
| `Module`, `ConfigProvider` | laminas-mvc module and its configuration; attaches the listener on bootstrap |
| `Listener\ResourceListener` | Resolves the routed resource; 404s missing or unpublished ones; sets resource and `PageMetadata` on the event |
| `ResourceParam` | Reads them from the event (`resource()`, `require()`, `metadata()`) |
| `Controller\Plugin\ResourcePlugin` | The `resource()` controller plugin |
| `Workflow\ResourceTreeAdapter` | workflow-laminas-mvc resource adapter over `ResourceRepository::findPageTree()` |
| `Workflow\WorkflowResource` | A resource entity presented as a workflow-laminas-mvc `ResourceInterface` |
| `Url\ResourceUrlGenerator` | Resource links through the router, editor-entered links through `ExternalUrl` |
| `Content\PartialSectionRenderer` | Renders section content with a view partial, for `ResourceSummary` |
| `View\Helper\ResourceHelper`, `ResourceMetaHelper`, `ResourceUrlHelper`, `ResourceContentHelper` | The view helpers |
| `Container\*Factory` | Container wiring |

Every concrete class is `final`. The docs cover each area:

- [Routing and navigation](docs/routing.md)
- [The route listener and the controller plugin](docs/controllers.md)
- [View helpers](docs/view-helpers.md)
- [Configuration](docs/configuration.md)
- [Coming from contenir-resource 1.x](docs/migration.md)

## Security

- **Unpublished pages.** The route cache can outlive a change in the admin. The listener re-checks every routed
  resource and answers missing, pending, inactive or archived ones with a 404; the controller never sees them. The
  controller plugin and view helpers only find active resources.
- **Request input.** Only an array `resource_id` route default with a `resourceId` key is treated as a resource route,
  so a path segment called `resource_id` can never trigger a lookup; ids other than positive integers never reach the
  database.
- **Host header.** Set `contenir_resource.base_url` in production; otherwise canonical and Open Graph URLs use the
  request's host, which the client controls. Query strings never reach canonical or share URLs.
- **Links and images.** Editor-entered links are limited to http, https, mailto and tel, and share images to http and
  https; `javascript:`, `data:` and the like are dropped.
- **Escaping.** `resourceMeta` writes through laminas-view's head helpers, which escape everything; summaries and link
  pairs are plain text that templates must escape.

## Development

The QA toolchain is [php-db/phpdb-qa-tools](https://github.com/php-db/phpdb-qa-tools).
[Mago](https://mago.carthage.software/) is a standalone binary, installed separately (`brew install mago`).

```bash
composer check             # everything below
composer cs-check          # mago format --check && mago lint
composer static-analysis   # mago analyze
composer test              # unit suite: no I/O
composer test-integration  # integration suite: a real laminas-mvc Application over in-memory SQLite
composer test-coverage     # both suites, clover.xml for Codecov
composer mutation-test     # Infection over both suites (needs Xdebug or PCOV)
```

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).

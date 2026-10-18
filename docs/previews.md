# CMS previews

With [contenir-preview-laminas-mvc](https://github.com/contenir/contenir-preview-laminas-mvc) loaded as well, a site
renders the Contenir CMS's previews of its resources with the editor's unsaved values, drafts and resources not saved yet
included, with no site code. This package supplies the resolvers that say which page shows a resource, and registers
contenir-resource's preview of the editor's values.

## Setting it up

Require the preview module and add `Contenir\Preview\Laminas\Mvc` to the application's modules. Then name a resolver for
each CMS section that edits resources, in the `contenir_preview` settings the site shares with the CMS:

```php
use Contenir\Resource\Laminas\Mvc\Preview\WorkflowArticleResolver;
use Contenir\Resource\Laminas\Mvc\Preview\WorkflowPageResolver;

return [
    'contenir_preview' => [
        'issuer'    => 'https://admin.example.com',
        'audience'  => 'https://www.example.com',
        'sign_with' => '2026-10',
        'resolvers' => [
            'site.page.home'           => WorkflowPageResolver::class,
            'library.articles.article' => WorkflowArticleResolver::class . '.news',
            'library.articles.event'   => WorkflowArticleResolver::class . '.event',
        ],
    ],
];
```

The secret goes in the local, unversioned file, as contenir-preview-laminas-mvc's README describes.

## The resolvers

| Resolver | Previews |
| --- | --- |
| `Preview\WorkflowPageResolver` | A page, at the controller its workflow (the CMS's Template field) names, with its id as its route would carry it. A changed slug or template shows as it will once saved. A page not saved yet has no id and is not previewed. |
| `Preview\WorkflowArticleResolver` | An article-like resource (a news article, an event, a package), on the detail view of the page that lists its kind: the active page whose workflow follows the dot in the service name (`….news`), at the slug the editor has typed. A resource not saved yet previews too. |

Both dispatch the controller directly rather than through the site's routes, so a preview works before its route
exists. `WorkflowArticleResolver` services are built by an abstract factory from their names; `WorkflowPageResolver`
has a factory of its own.

## The editor's values

The values come from [contenir-resource](https://github.com/contenir/contenir-resource/blob/main/docs/previews.md):
while a preview is rendered, its delegator wraps the `ResourceManagerInterface` service so a lookup that finds the
previewed resource gets it with the editor's values, lists included. This package registers that delegator, so the
controller plugin, the view helpers and the route listener all see the preview however a template loads the resource.

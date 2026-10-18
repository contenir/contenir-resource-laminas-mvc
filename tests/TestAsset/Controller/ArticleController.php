<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Controller;

use Contenir\Resource\Core\ResourceManagerInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

use function is_string;

/**
 * A listing page's controller, as a site's news page has one: its detail view finds the article
 * by the slug in its address, among the published articles.
 */
final class ArticleController extends AbstractActionController
{
    public function indexAction(): ViewModel
    {
        return (new ViewModel(['article' => null]))->setTemplate('test/article');
    }

    public function viewAction(): ViewModel
    {
        /** @var mixed $resources */
        $resources = $this->plugin('resource')();
        /** @var mixed $slug */
        $slug    = $this->params()->fromRoute('slug');
        $article = $resources instanceof ResourceManagerInterface && is_string($slug)
            ? $resources->findActiveBySlug($slug)
            : null;
        if (null === $article) {
            $this->getResponse()->setStatusCode(404);
        }

        return (new ViewModel(['article' => $article]))->setTemplate('test/article');
    }
}

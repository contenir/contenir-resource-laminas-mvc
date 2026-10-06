<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Controller;

use Contenir\Resource\Laminas\Mvc\Controller\Plugin\ResourcePlugin;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

/**
 * Renders the routed resource with the test/page template, using the
 * resource() plugin the way a site controller does.
 */
final class PageController extends AbstractActionController
{
    public function indexAction(): ViewModel
    {
        $plugin = $this->plugin('resource');
        if (! $plugin instanceof ResourcePlugin) {
            return new ViewModel();
        }

        $page  = $plugin->routed();
        $model = new ViewModel([
            'page'     => $page,
            'metadata' => $plugin->metadata(),
            'byRoute'  => $plugin($this->params()->fromRoute('resource_id')),
        ]);
        $model->setTemplate('test/page');

        return $model;
    }
}

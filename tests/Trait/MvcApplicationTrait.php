<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Trait;

use Contenir\Mvc\Workflow\Strategy\ResourceStrategyInterface;
use Contenir\Mvc\Workflow\Workflow\WorkflowFactory;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Controller\PageController;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Workflow\PageControllerWorkflow;
use Contenir\Resource\Laminas\Mvc\Workflow\ResourceTreeAdapter;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\SendResponseListener;
use Laminas\ServiceManager\Factory\InvokableFactory;
use PhpDb\Adapter\AdapterInterface;

use function dirname;

/**
 * A real laminas-mvc Application per test, bootstrapped with the
 * Laminas\Router, Contenir\Db\Model, Contenir\Mvc\Workflow and this
 * package's modules over the test's SQLite adapter, with a "page" workflow
 * routing to PageController and the fixture templates. The response is
 * kept, not sent. Needs SqliteDatabaseTrait.
 */
trait MvcApplicationTrait
{
    /**
     * @param array<string, mixed> $resourceConfig
     * @param array<string, mixed> $routes     Extra router routes.
     * @param StorageInterface|null $routeCache The strategy's route cache, shared between applications.
     *
     * @mago-expect lint:no-literal-namespace-string Module names are namespaces, not classes.
     */
    private function bootstrapApplication(
        array $resourceConfig = [],
        array $routes = [],
        ?StorageInterface $routeCache = null,
    ): Application {
        $view = dirname(__DIR__) . '/TestAsset/templates';

        $application = Application::init([
            'modules'                 => [
                'Laminas\Router',
                'Contenir\Db\Model',
                'Contenir\Mvc\Workflow',
                'Contenir\Resource\Laminas\Mvc',
            ],
            'module_listener_options' => [
                'use_laminas_loader'       => false,
                'config_cache_enabled'     => false,
                'module_map_cache_enabled' => false,
                'extra_config'             => [
                    'contenir_resource' => $resourceConfig,
                    'router'            => ['routes' => $routes],
                    'service_manager'   => ['services' => [AdapterInterface::class => $this->adapter]],
                    'controllers'       => ['factories' => [PageController::class => InvokableFactory::class]],
                    'workflow_manager'  => [
                        'aliases'   => ['page' => PageControllerWorkflow::class],
                        'factories' => [PageControllerWorkflow::class => WorkflowFactory::class],
                        'strategy'  => [
                            'type'       => ResourceStrategyInterface::class,
                            'repository' => ResourceTreeAdapter::class,
                            'options'    => ['cache' => $routeCache],
                        ],
                    ],
                    'view_manager'      => [
                        'doctype'                  => 'HTML5',
                        'display_exceptions'       => true,
                        'display_not_found_reason' => true,
                        'not_found_template'       => 'error/404',
                        'exception_template'       => 'error/index',
                        'template_map'             => [
                            'layout/layout'                  => "{$view}/layout/layout.phtml",
                            'error/404'                      => "{$view}/error/404.phtml",
                            'error/index'                    => "{$view}/error/index.phtml",
                            'test/page'                      => "{$view}/test/page.phtml",
                            'application/component/_section' => "{$view}/application/component/_section.phtml",
                        ],
                    ],
                ],
            ],
        ]);

        $sender = $application->getServiceManager()->get(SendResponseListener::class);
        static::assertInstanceOf(SendResponseListener::class, $sender);
        $sender->detach($application->getEventManager());

        return $application;
    }

    private function dispatch(Application $application, string $url): Response
    {
        $request = $application->getRequest();
        static::assertInstanceOf(Request::class, $request);
        $request->setUri($url);
        $request->setBaseUrl('');

        $application->run();
        $response = $application->getResponse();
        static::assertInstanceOf(Response::class, $response);

        return $response;
    }
}

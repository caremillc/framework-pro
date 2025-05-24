<?php declare(strict_types=1);

namespace Careminate\Http;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class Kernel
{
    protected $routes = [];

    public function __construct()
    {
        // Initialize routes array for dynamic route registration
    }

    public function handle(Request $request): Response
    {
        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) {
            $routes = require_once route_path('web.php');
            foreach ($routes as $route) {
                $routeCollector->addRoute(...$route);
            }
        });

        $routeInfo = $dispatcher->dispatch(
            $request->getMethod(),
            $request->getPathInfo()
        );

        switch ($routeInfo[0]) {
            case Dispatcher::FOUND:
                [$handler, $vars] = [$routeInfo[1], $routeInfo[2]];

                // Ensure $handler is [ControllerClass::class, 'method']
                [$controller, $method] = $handler;

                // return (new $controller())->$method($vars);
                return call_user_func_array([new $controller, $method], $vars);

            case Dispatcher::METHOD_NOT_ALLOWED:
                return new Response('<h1>405 Method Not Allowed</h1>', 405);

            case Dispatcher::NOT_FOUND:
                return new Response('<h1>404 Not Found</h1>', 404);

            default:
                return new Response('<h1>Unexpected routing error</h1>', 500);
        }
    }
}


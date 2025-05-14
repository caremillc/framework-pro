<?php declare(strict_types=1);
namespace Careminate\Routing;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Careminate\Http\Requests\Request;
use Careminate\Exceptions\HttpException;
use function FastRoute\simpleDispatcher;
use Careminate\Exceptions\HttpRequestMethodException;

class Router implements RouterInterface
{
    public function dispatch(Request $request): array
    {
        $routeInfo = $this->extractRouteInfo($request);

        [$handler, $vars] = $routeInfo;

        [$controller, $method] = $handler;

        return [[new $controller, $method], $vars];

    }

    private function extractRouteInfo(Request $request)
    { 
        $requestedPath = $request->getPathInfo(); // Get requested URI
        
        // Ignore requests for favicon.ico
       if ($requestedPath === '/favicon.ico') {
           return [null, []]; // Return a no-op response
       }

        // Create a dispatcher
        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) {

               // Dynamically load routes from the external file
            $routes = require_once route_path('web.php');

            foreach ($routes as $route) {
                $routeCollector->addRoute(...$route);
            }
        });

        // Dispatch a URI, to obtain the route info
        $routeInfo = $dispatcher->dispatch(
            $request->getMethod(),
            $requestedPath
        );

        switch ($routeInfo[0]) {
            case Dispatcher::FOUND:
                return [$routeInfo[1], $routeInfo[2]]; // routeHandler, vars
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = implode(', ', $routeInfo[1]);
                throw new HttpRequestMethodException("The allowed methods are $allowedMethods");
            default:
                throw new HttpException('Not found');
        }
    }
}

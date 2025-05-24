<?php declare (strict_types = 1);
namespace Careminate\Routing;

use Careminate\Exceptions\HttpException;
use Careminate\Exceptions\HttpRequestMethodException;
use Careminate\Http\Requests\Request;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class Router implements RouterInterface
{
    public function dispatch(Request $request): array
    {
        $routeInfo = $this->extractRouteInfo($request);

        // If routeInfo is null (e.g., for favicon or similar), short-circuit gracefully
        if ($routeInfo === null) {
            return [[fn() => new \Careminate\Http\Responses\Response('', 204), '__invoke'], []];
        }

        [$handler, $vars] = $routeInfo;

        if (! is_array($handler) || ! is_string($handler[0]) || ! is_string($handler[1])) {
            throw new \InvalidArgumentException('Invalid route handler definition.');
        }

        [$controller, $method] = $handler;

        return [[new $controller, $method], $vars];
    }


    private function extractRouteInfo(Request $request): array | null
    {
        $requestedPath = $request->getPathInfo();

           // Ignore requests for favicon.ico
       if ($requestedPath === '/favicon.ico') {
           return [null, []]; // Return a no-op response
       }

        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) {
            $routes = require_once route_path('web.php');
            foreach ($routes as $route) {
                $routeCollector->addRoute(...$route);
            }
        });

        $routeInfo = $dispatcher->dispatch(
            $request->getMethod(),
            $requestedPath
        );

        switch ($routeInfo[0]) {
            case Dispatcher::FOUND:
                return [$routeInfo[1], $routeInfo[2]];
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = implode(', ', $routeInfo[1]);
                throw new HttpRequestMethodException("The allowed methods are $allowedMethods", 405);
            default:
                throw new HttpException('Not found', 404);
        }
    }

}

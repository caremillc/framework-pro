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
    private array $routes;

    public function setRoutes(array $routes): void
    {
        //$routes is parsed from setRoutes in $container
        $this->routes = $routes;
    }

    public function dispatch(Request $request): array
    {
        $routeInfo = $this->extractRouteInfo($request);

        // If favicon or null handler
        if ($routeInfo === null) {
            return [[fn() => new \Careminate\Http\Responses\Response('', 204), '__invoke'], []];
        }

        [$handler, $vars] = $routeInfo;

        // Validate the handler
        if (! is_array($handler) || ! is_string($handler[0]) || ! is_string($handler[1])) {
            throw new \InvalidArgumentException('Invalid route handler definition.');
        }

        [$controller, $method] = $handler;

        return [[new $controller(), $method], $vars];
    }

    private function extractRouteInfo(Request $request): array | null
    {
        $requestedPath = $request->getPathInfo();

        if ($requestedPath === '/favicon.ico') {
            return null;
        }

        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) {
            $routes = require route_path('web.php');

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
                throw new HttpException('Not Found', 404);
        }
    }

}

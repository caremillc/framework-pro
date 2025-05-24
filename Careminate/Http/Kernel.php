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
        // Create the dispatcher with dynamic route registration
        // Create a dispatcher
        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) {

               // Dynamically load routes from the external file
            $routes = require_once route_path('web.php');

            foreach ($routes as $route) {
                $routeCollector->addRoute(...$route);
            }

        });
        // Dispatch the request URI and method
        $routeInfo = $dispatcher->dispatch(
            $request->getMethod(),
            $request->getPathInfo()
        );

        // Unpack route information
        [$status, $handler, $vars] = $routeInfo;

        // dd($routeInfo);

        // Handle the route and return a response
        switch ($status) {
            case Dispatcher::FOUND:
                // Route matched, call the handler and return the response
                // return $handler($vars);
                [$status, [$controller, $method], $vars] = $routeInfo;

                // Call the handler, provided by the route info, in order to create a Response
                $response = (new $controller())->$method($vars);

                // Call the handler, provided by the route info, in order to create a Response
                return $response;

            case Dispatcher::METHOD_NOT_ALLOWED:
                // Method not allowed for this route, handle error
                return new Response('<h1>405 Method Not Allowed</h1>', 405);

            case Dispatcher::NOT_FOUND:
                // No matching route found, handle 404 error
                return new Response('<h1>404 Not Found</h1>', 404);
        }

        // Fallback: if status is not found
        return new Response('<h1>Something went wrong</h1>', 500);

    }

}

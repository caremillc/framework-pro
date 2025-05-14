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
        // Initialize routes array for dynamic route registration if needed.
    }

    /**
     * Handles the incoming request and returns the appropriate response.
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        // Create the dispatcher with dynamic route registration from the 'web.php' routes file
        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) {
            // Dynamically load routes from the external file
            $routes = require_once route_path('web.php');

            foreach ($routes as $route) {
                // Register each route
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

        // Handle the route and return a response based on the dispatch status
        switch ($status) {
            case Dispatcher::FOUND:
                // Route matched, execute handler and return response
                return $handler($vars);

            case Dispatcher::METHOD_NOT_ALLOWED:
                // Method not allowed for this route, handle error
                return new Response('<h1>405 Method Not Allowed</h1>', 405);

            case Dispatcher::NOT_FOUND:
                // No matching route found, handle 404 error
                return new Response('<h1>404 Not Found</h1>', 404);
        }

        // Fallback: If an unknown error occurs, return a generic 500 response
        return new Response('<h1>Something went wrong</h1>', 500);
    }
}


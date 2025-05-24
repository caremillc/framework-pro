<?php declare(strict_types=1);
namespace Careminate\Http;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;

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
        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) {
            // Register GET routes
            $routeCollector->addRoute('GET', '/', function () {

                $content = '<h1>Hello World</h1>';

                return new Response($content);
            });

            // Register POST route
            $routeCollector->addRoute('GET', '/posts', function () {
                $content = "<h1>All Post</h1>, 201";
                return new Response($content);
            });
            // Register POST route
            $routeCollector->addRoute('GET', '/posts/create', function () {
                $content = "<h1>Post Created</h1>, 201";
                return new Response($content);
            });

            // Register POST route
            $routeCollector->addRoute('POST', '/posts/store', function () {
                $content = "<h1>Store Post</h1>, 301";
                return new Response($content);
            });

            // Register a parameterized GET route
            $routeCollector->addRoute('GET', '/posts/{id:\d+}/show', function ($vars) {
                $content = "<h1>This is Post {$vars['id']}</h1>";
                return new Response($content);
            });

            // Register a parameterized GET route
            $routeCollector->addRoute('GET', '/posts/{id:\d+}/edit', function ($vars) {
                $content = "<h1>This is Post {$vars['id']}</h1>";
                return new Response($content);
            });
            // Register PUT route
            $routeCollector->addRoute('PUT', '/posts/{id:\d+}/update', function ($vars) {
                $content = "<h1>Post {$vars['id']} Updated</h1>";
                return new Response($content);
            });

            // Register DELETE route
            $routeCollector->addRoute('DELETE', '/posts/{id:\d+}/delete', function ($vars) {
                $content = "<h1>Post {$vars['id']} Deleted</h1>";
                return new Response($content);
            });

            // Example of handling a wildcard route for a catch-all (e.g., for an API)
            $routeCollector->addRoute('GET', '/{wildcard:.+}', function ($vars) {
                $content = "<h1>Wildcard match for: {$vars['wildcard']}</h1>";
                return new Response($content);
            });
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
                return $handler($vars);

            case Dispatcher::METHOD_NOT_ALLOWED:
                // Method not allowed for this route, handle error
                return new Response('<h1>405 Method Not Allowed</h1>', 405);

            case Dispatcher::NOT_FOUND:
                // No matching route found, handle 404 error
                return new Response('<h1>404 Not Found</h1>', 404);
        }

        // Handle 404 Not Found if no matching route is found
        return new Response("<h1>404 Not Found</h1>", 404);
    }

}

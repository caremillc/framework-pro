<?php declare(strict_types=1);
namespace Careminate\Routing;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Careminate\Http\Requests\Request;
use Psr\Container\ContainerInterface;
use Careminate\Exceptions\HttpException;
use function FastRoute\simpleDispatcher;
use Careminate\Exceptions\HttpRequestMethodException;

class Router implements RouterInterface
{ 
    private array $routes;
    
    public function setRoutes(array $routes): void
    {
        //$routes is parsed from setRoutes in $container
        $this->routes = $routes;
    }

    public function dispatch(Request $request, ContainerInterface $container): array  // this ContainerInterface
    {
        // Extract route info based on the current request
        $routeInfo = $this->extractRouteInfo($request);

        // Unpack route handler and variables
        [$handler, $vars] = $routeInfo;

        if (is_array($handler)) {
            // Extract the controller and method from the handler
            [$controllerId, $method] = $handler;
            $controller = $container->get($controllerId);  // this code
            $handler = [$controller, $method];
        }

        // Return the controller and method along with route variables
        return [$handler, $vars];
    }
    private function extractRouteInfo(Request $request): array
    { 
	  $requestedPath = $request->getPathInfo(); // Get requested URI
        
        // Ignore requests for favicon.ico
       if ($requestedPath === '/favicon.ico') {
           return [null, []]; // Return a no-op response
       }
	   
        // Create a dispatcher
        $dispatcher = simpleDispatcher(function (RouteCollector $routeCollector) {

              // Dynamically load routes from the external file
            //   $routes = require_once route_path('web.php');

            foreach ($this->routes as $route) {   // $this->routes
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

<?php declare(strict_types=1);
namespace Careminate\Http\Middlewares;

use Careminate\Http\Requests\Request;
use Psr\Container\ContainerInterface;
use Careminate\Http\Responses\Response;
use Careminate\Http\Middlewares\Contracts\RequestHandlerInterface;

class RequestHandler implements RequestHandlerInterface
{
  private array $middleware = [
        StartSession::class,  // this
        Authenticate::class,
        RouterDispatch::class,
        // SuccessMiddleware::class
		// Authenticate::class,
    ];
    
    public function __construct(private ContainerInterface $container){}  //step 1
     
    public function handle(Request $request): Response
    {
        // If there are no middleware classes to execute, return a default response
        // A response should have been returned before the list becomes empty
        if (empty($this->middleware)) {
            return new Response("It's totally borked, please. Contact support", 500);
        }

        // Get the next middleware class to execute
        $middlewareClass = array_shift($this->middleware);

        // Get middleware from container
        $middleware = $this->container->get($middlewareClass);  // step 2

        // Create a new instance of the middleware call process on it
        // $response = (new $middlewareClass())->process($request, $this);
        $response = $middleware->process($request, $this); // step 3

        return $response;
    }
}

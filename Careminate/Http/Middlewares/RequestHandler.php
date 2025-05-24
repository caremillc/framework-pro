<?php declare(strict_types=1);
namespace Careminate\Http\Middlewares;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Careminate\Http\Middlewares\Contracts\RequestHandlerInterface;

class RequestHandler implements RequestHandlerInterface
{
    private array $middleware = [
        Authenticate::class,
        SuccessMiddleware::class
    ];

    public function handle(Request $request): Response
    {
        // If there are no middleware classes to execute, return a default response
        // A response should have been returned before the list becomes empty
        if (empty($this->middleware)) {
            return new Response("It's totally borked, please. Contact support", 500);
        }

        // Get the next middleware class to execute
        $middlewareClass = array_shift($this->middleware);

        // Create a new instance of the middleware call process on it
        $response = (new $middlewareClass())->process($request, $this);

        return $response;
    }
}


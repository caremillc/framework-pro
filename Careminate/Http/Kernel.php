<?php declare(strict_types=1);

namespace Careminate\Http;

use Careminate\Routing\Router;
use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Careminate\Exceptions\HttpException;

/**
 * HTTP Kernel
 * 
 * The Kernel class serves as the central point for handling incoming HTTP requests
 * and returning the appropriate responses by dispatching them through the router.
 */
class Kernel
{
    public function __construct(private Router $router)
    {
    }

    public function handle(Request $request): Response
    {
        try {

            [$routeHandler, $vars] = $this->router->dispatch($request);

            $response = call_user_func_array($routeHandler, $vars);

        } catch (HttpException $exception) {
            $response = new Response($exception->getMessage(), $exception->getStatusCode());
        } catch (\Exception $exception) {
            $response = new Response($exception->getMessage(), 500);
        }

        return $response;
    }
}

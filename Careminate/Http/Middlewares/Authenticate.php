<?php declare(strict_types=1);
namespace Careminate\Http\Middlewares;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Careminate\Http\Middlewares\Contracts\MiddlewareInterface;
use Careminate\Http\Middlewares\Contracts\RequestHandlerInterface;

class Authenticate implements MiddlewareInterface
{
    private bool $authenticated = true;

    public function process(Request $request, RequestHandlerInterface $requestHandler): Response
    {
        if (!$this->authenticated) {
            return new Response('Authentication failed', 401);
        }

        return $requestHandler->handle($request);
    }
}
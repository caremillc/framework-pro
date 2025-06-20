<?php declare(strict_types=1);
namespace Careminate\Http\Middlewares;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Careminate\Http\Middlewares\Contracts\MiddlewareInterface;
use Careminate\Http\Middlewares\Contracts\RequestHandlerInterface;

class DummyMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $requestHandler): Response
    {
        return $requestHandler->handle($request);
    }
}
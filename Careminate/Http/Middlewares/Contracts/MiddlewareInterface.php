<?php declare(strict_types=1);
namespace Careminate\Http\Middlewares\Contracts;

use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;

interface MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $requestHandler): Response;
}
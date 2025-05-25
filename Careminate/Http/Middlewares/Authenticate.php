<?php declare(strict_types=1);
namespace Careminate\Http\Middlewares;

use Careminate\Sessions\Session;
use Careminate\Http\Requests\Request;
use Careminate\Http\Responses\Response;
use Careminate\Sessions\SessionInterface;
use Careminate\Http\Middlewares\Contracts\MiddlewareInterface;
use Careminate\Http\Middlewares\Contracts\RequestHandlerInterface;

class Authenticate implements MiddlewareInterface
{
    // private bool $authenticated = true;
     public function __construct(private SessionInterface $session){}

    public function process(Request $request, RequestHandlerInterface $requestHandler): Response
    {
        // if (!$this->authenticated) {
        //     return new Response('Authentication failed', 401);
        // }
         if (!$this->session->has(Session::AUTH_KEY)) {
            flash('error', 'Please sign in');
            return redirect('/login');
        }

        return $requestHandler->handle($request);
    }
}

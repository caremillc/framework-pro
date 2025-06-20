<?php declare(strict_types=1);
namespace Careminate\Http;

use Doctrine\DBAL\Connection;
use Careminate\Http\Requests\Request;
use Psr\Container\ContainerInterface;
use Careminate\Http\Responses\Response;
use Careminate\Routing\RouterInterface;
use Careminate\Exceptions\HttpException;
use Careminate\Databases\Dbal\EventDispatcher\ResponseEvent;
use Careminate\Databases\Dbal\EventDispatcher\EventDispatcher;
use Careminate\Http\Middlewares\Contracts\RequestHandlerInterface;

class Kernel
{
    private string $appEnv;
    private string $appKey;
    private string $appVersion;

    public function __construct(
        //private RouterInterface $router,
        private ContainerInterface $container,
        private RequestHandlerInterface $requestHandler,  //step 1
         private EventDispatcher $eventDispatcher  //step 1
    ){
        // Check .env file and configuration values
        if (!file_exists('.env') || !is_readable('.env')) {
            throw new \RuntimeException('.env file is missing or not readable.');
        }

        $this->appEnv = $this->container->get('APP_ENV');
        $this->appKey = $this->container->get('APP_KEY');
        $this->appVersion = $this->container->get('APP_VERSION');

        if (empty($this->appKey) || empty($this->appEnv) || empty($this->appVersion)) {
            throw new \RuntimeException('One or more required environment variables are missing.');
        }
    }

    public function handle(Request $request): Response
    {
        // Early return for favicon.ico if not explicitly handled
        if ($request->getPathInfo() === '/favicon.ico') {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        try {
              $response = $this->requestHandler->handle($request);  //step 2
           
        } catch (HttpException $exception) {
            $response = $this->createExceptionResponse($exception);
        }

         $this->eventDispatcher->dispatch(new ResponseEvent($request, $response));  //step 2 
         
        return $response;
    }

     private function createExceptionResponse(\Exception $exception): Response
	{
		// Check if the environment is development or local testing
		if (in_array($this->appEnv, ['dev', 'local', 'test'])) {
			// In development or local testing, rethrow the exception for detailed debugging
			throw $exception;
		}

		// Production environment handling
		if ($exception instanceof HttpException) {
			// Return a response with the HTTP status and message for HTTP exceptions
			return new Response($exception->getMessage(), $exception->getStatusCode());
		}

		// For all other exceptions, return a generic server error message
		return new Response('Server error', Response::HTTP_INTERNAL_SERVER_ERROR);
	}

    //  public function terminate(Request $request, Response $response): void
    // {
    //     $request->getSession()?->clearFlash();
    // }
      public function terminate(Request $request, Response $response): void
	{
		if ($request->hasSession()) {
			$session = $request->getSession();
			// Perform termination tasks with session
			// $request->getSession()?->clearFlash();
			$session?->clearFlash();
		}
	}
}


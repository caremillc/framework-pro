<?php declare(strict_types=1);
namespace Careminate\Http\Controllers;

use Careminate\Http\Requests\Request;
use Psr\Container\ContainerInterface;
use Careminate\Http\Responses\Response;

abstract class AbstractController
{
    protected ?ContainerInterface $container = null;
    protected Request $request;
  
    public function setContainer(ContainerInterface $container): void
    {
        // Store the container instance for use within the controller.
        $this->container = $container;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }
    
    public function render(string $template, array $parameters = [], ?Response $response = null): Response
    {
        // Render the template using the Twig service from the container.
        $content = $this->container->get('twig')->render($template, $parameters);

        // If no response object is passed, create a new one.
        $response ??= new Response();

        // Set the rendered content as the response body.
        $response->setContent($content);

        // Return the response object with the rendered content.
        return $response;
    }
    
}

<?php declare(strict_types=1); 
namespace Careminate\Template;

use Twig\Environment;
use Twig\TwigFunction;
use Twig\Loader\FilesystemLoader;
use Twig\Extension\DebugExtension;
use Careminate\Sessions\SessionInterface;

class TwigFactory
{
     public function __construct(
        private SessionInterface $session,
        private string $templatesPath
    ){}

    public function create(): Environment
    {
        // instantiate FileSystemLoader with templates path
        $loader = new FilesystemLoader($this->templatesPath);

        // instantiate Twig Environment with loader
        $twig = new Environment($loader, [
            'debug' => true,
            'cache' => false,
        ]);

        // add new twig session() function to Environment
        $twig->addExtension(new DebugExtension());
        // add session
        $twig->addFunction(new TwigFunction('session', [$this, 'getSession']));
        return $twig;
    }

   public function getSession(): SessionInterface
    {
        return $this->session;
    }
  
}

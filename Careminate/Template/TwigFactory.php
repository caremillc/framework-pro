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

         // Pass environment variables to Twig templates
        $this->addEnvironmentVariablesToTwig($twig);

              // Add the title variable if it exists in the session or globally
       $title = $this->getTitleFromContext();
       $twig->addGlobal('title', $title);

        return $twig;
    }

   public function getSession(): SessionInterface
    {
        return $this->session;
    }
  private function addEnvironmentVariablesToTwig(Environment $twig): void
   {
       // Define the configuration file path
       $configPath = __DIR__ . '/../../../config/app.php'; // Ensure this is correct for your app structure
   
       // Ensure the config file exists and is readable
       if (!file_exists($configPath)) {
           throw new \RuntimeException("Config file not found: {$configPath}");
       }
   
       // Load the config file directly, assuming it's a PHP file returning an array
       $config = include $configPath;
       
       // Ensure the config is an array
       if (!is_array($config)) {
           throw new \RuntimeException("Config file should return an array. Invalid format in: {$configPath}");
       }
   
       // Add environment variables to Twig as global variables
       $twig->addGlobal('app_name', $config['name'] ?? '');  // Default to empty string if not set
       $twig->addGlobal('app_version', $config['version'] ?? '1.0.0');  // Default to 1.0.0
       $twig->addGlobal('app_env', $config['env'] ?? 'production');  // Default to 'production'
       $twig->addGlobal('app_url', $config['url'] ?? 'http://localhost:8000'); // Default to local URL
   
       // You can easily add more variables here following the same pattern
   }

   /**
     * Retrieve the title from the session or any globally accessible context.
     */
    private function getTitleFromContext(): string
    {
        // Check if a title is set in the session or globally (for example, via controller or other context)
        return $this->session->get('title') ?? config('app.name'); ; // Default to an empty string if no title exists
    }
}

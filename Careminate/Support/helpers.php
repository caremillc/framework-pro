<?php declare (strict_types = 1);

use Careminate\Http\Responses\Response;

// Just include the file at the top of your script
// require_once 'debug_functions.php';

if (! function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    function value(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (! function_exists('base_path')) {
    function base_path(?string $file = null)
    {
        return ROOT_DIR . '/../' . $file;
    }
}

if (! function_exists('config')) {
    function config(?string $file = null)
    {
        $seprate = explode('.', $file);
        if ((! empty($seprate) && count($seprate) > 1) && ! is_null($file)) {
            $file = include base_path('config/') . $seprate[0] . '.php';
            return isset($file[$seprate[1]]) ? $file[$seprate[1]] : $file;
        }
        return $file;
    }
}

if (! function_exists('route_path')) {
    function route_path(?string $file = null)
    {
        return ! is_null($file) ? config('route.path') . '/' . $file : config('route.path');
    }
}

// Env Function
if (! function_exists('env')) {
    /**
     * Get an environment variable, or return the default value if not found.
     *
     * Supports various data types.
     *
     * @param string $key The name of the environment variable.
     * @param mixed $default The default value to return if the environment variable is not found.
     * @return mixed The value of the environment variable or the default value.
     */
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;

        if (! is_string($value)) {
            return $value;
        }

        $trimmedValue = trim($value);

        return match (strtolower($trimmedValue)) {
            'true' => true,
            'false' => false,
            'null' => null,
            'empty' => '',
            default => is_numeric($trimmedValue) ? (str_contains($trimmedValue, '.') ? (float) $trimmedValue : (int) $trimmedValue) : (
                preg_match('/^[\[{].*[\]}]$/', $trimmedValue) ? (json_decode($trimmedValue, true) ?? $trimmedValue) : $trimmedValue
            )
        };
    }
}

if (!function_exists('view')) {
    function view(string $template, array $parameters = [], ?Response $response = null): Response
    {
        // Access the global container
        global $container;

        // Make sure the container is set
        if (!isset($container)) {
            throw new RuntimeException('Container is not set.');
        }

        $content = $container->get('twig')->render($template, $parameters);

        $response ??= new Response();
        $response->setContent($content);

        return $response;
    }
}


if (! function_exists('storage_path')) {
   function storage_path(string $path = ''): string
    {
        return BASE_PATH . '/storage' . ($path ? '/' . ltrim($path, '/') : '');
    }
}


if (!function_exists('redirect')) {
    /**
     * Helper function to generate a redirect response.
     *
     * @param string $url The URL to redirect to.
     * @param int $status The HTTP status code (default is 302).
     * @param array $headers Any additional headers for the redirect.
     * @return Response The redirect response.
     */
    function redirect(string $url, int $status = 302, array $headers = []): Response
    {
        // Create a new redirect response using the provided parameters
        $headers['Location'] = $url;

        return new Response('', $status, $headers);
    }
}
/**
 * Sets a flash message in the session.
 *
 * This function sets a flash message with a specified type and message content.
 * The message will be available on the next request and then automatically cleared.
 *
 * @param string $type The type of the flash message (e.g., 'success', 'error').
 * @param string $message The message content to be set in the flash data.
 *
 * @return void
 */
function flash($type, $message)
{
    // Assuming $container is globally accessible or you have a way to get the session
    global $container;

    // Retrieve the session from the container
    $session = $container->get(\Careminate\Sessions\SessionInterface::class);

    // Set the flash message
    $session->setFlash($type, $message);
}



if (!function_exists('asset')) {
    /**
     * Generate a full URL for an asset in the public directory.
     *
     * @param  string  $path
     * @return string
     */
    function asset(string $path): string
    {
        // Infer scheme reliably
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        // Default to localhost if HTTP_HOST isn't set
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';

        return rtrim($scheme . '://' . $host, '/') . '/' . ltrim($path, '/');
    }
}

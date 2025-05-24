<?php declare (strict_types = 1);

// Just include the file at the top of your script
require_once 'debug_functions.php';

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

<?php declare(strict_types=1);
namespace Careminate\Http\Requests;

use Careminate\Support\Arr;
use Careminate\Sessions\SessionInterface;

/**
 * HTTP Request class that handles and normalizes request data
 */
class Request
{
     private SessionInterface $session;
     
    /**
     * HTTP methods that can contain request body data
     */
    private const METHODS_WITH_BODY = ['POST', 'PUT', 'PATCH', 'DELETE'];
    
    /**
     * Valid HTTP methods for spoofing
     */
    private const SPOOFABLE_METHODS = ['PUT', 'PATCH', 'DELETE'];

    /**
     * Request constructor
     */
    public function __construct(
        private readonly array $getParams = [],
        private readonly array $postParams = [],
        private readonly array $cookies = [],
        private readonly array $files = [],
        private readonly array $server = [],
        public readonly array $inputParams = [],
        public readonly string $rawInput = ''
    ) {}

    /**
     * Create a new request instance from global variables
     */
    public static function createFromGlobals(): static
    {
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $rawInput = file_get_contents('php://input');
        $inputParams = [];
        
        // Only process input data when necessary
        if ($rawInput !== '') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (str_contains($contentType, 'application/json')) {
                $inputParams = json_decode($rawInput, true) ?? [];
            } elseif (!in_array($requestMethod, ['GET', 'POST'], true)) {
                parse_str($rawInput, $inputParams);
            }
        }

        return new static($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER, $inputParams, $rawInput);
    }

    /**
     * Get the request method, taking into account method spoofing
     */
    public function getMethod(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST') {
            $spoofedMethod = strtoupper(
                $this->postParams['_method'] ?? 
                $this->header('X-HTTP-Method-Override') ?? ''
            );

            if (in_array($spoofedMethod, self::SPOOFABLE_METHODS, true)) {
                return $spoofedMethod;
            }
        }

        return $method;
    }

    /**
     * Get a request header value by name
     */
    public function header(string $name): ?string
    {
        $name = strtoupper(str_replace('-', '_', $name));
        $serverKey = match ($name) {
            'CONTENT_TYPE', 'CONTENT_LENGTH' => $name,
            default => 'HTTP_' . $name
        };
        
        return $this->server[$serverKey] ?? null;
    }

    /**
     * Get all request headers
     */
    public function headers(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[$key] = $value;
            }
        }
        
        return $headers;
    }

    /**
     * Get the full URL including scheme, host, and path
     */
    public function fullUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        
        return sprintf(
            '%s://%s%s',
            $scheme,
            $this->server['HTTP_HOST'] ?? '',
            $this->server['REQUEST_URI'] ?? ''
        );
    }

    /**
     * Get the request path info
     */
    public function getPathInfo(): string
    {
        return (string)parse_url($this->server['REQUEST_URI'] ?? '', PHP_URL_PATH);
    }

    /**
     * Get a value from the consolidated request data
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getParams[$key] ?? 
               $this->postParams[$key] ?? 
               $this->inputParams[$key] ?? 
               $default;
    }

    /**
     * Check if a key exists in the request data
     */
    public function has(string $key): bool
    {
        return isset($this->getParams[$key]) || 
               isset($this->postParams[$key]) || 
               isset($this->inputParams[$key]);
    }

    /**
     * Get a cookie value
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get an uploaded file
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Check if a file was uploaded
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]['tmp_name']) &&
               is_uploaded_file($this->files[$key]['tmp_name']);
    }

    /**
     * Get all uploaded files
     */
    public function allFiles(): array
    {
        return $this->files;
    }

    /**
     * Get all input data including GET, POST, and parsed input
     */
    public function all(): array
    {
        return array_merge($this->getParams, $this->postParams, $this->inputParams);
    }

    /**
     * Get a value from the POST or parsed input data
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $this->inputParams[$key] ?? $default;
    }

    /**
     * Get a value from the query string
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->getParams[$key] ?? $default;
    }

    /**
     * Get a value from POST data
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $default;
    }

    /**
     * Get a server variable
     */
    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Get the raw request body
     */
    public function getRawInput(): string
    {
        return $this->rawInput;
    }

    /**
     * Check if the request has JSON content type
     */
    public function isJson(): bool
    {
        return (bool)preg_match('~[/+]json\b~i', $this->header('Content-Type') ?? '');
    }

    /**
     * Check if the client prefers JSON response
     */
    public function wantsJson(): bool
    {
        $accept = $this->header('Accept') ?? '';
        return (bool)preg_match('~[/+]json\b~i', $accept);
    }

    /**
     * Check if the request is over HTTPS
     */
    public function isSecure(): bool
    {
        return ($this->server['HTTPS'] ?? '') === 'on' ||
               ($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    /**
     * Get the client IP address
     */
    public function ip(): string
    {
        return $this->server['HTTP_CLIENT_IP'] ??
               $this->server['HTTP_X_FORWARDED_FOR'] ??
               $this->server['REMOTE_ADDR'] ?? 
               '';
    }

    /**
     * Get the User-Agent header
     */
    public function userAgent(): ?string
    {
        return $this->header('User-Agent');
    }

    /**
     * Get only the specified keys from the request data
     */
    public function only(array|string $keys): array
    {
        return Arr::only(
            $this->all(), 
            is_string($keys) ? func_get_args() : $keys
        );
    }

    /**
     * Get all except the specified keys from the request data
     */
    public function except(array|string $keys): array
    {
        return Arr::except(
            $this->all(), 
            is_string($keys) ? func_get_args() : $keys
        );
    }

    /**
     * Check if the request method matches the given method
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->getMethod();
    }

    /**
     * Check if the request method is POST
     */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * Check if the request method is GET
     */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /**
     * Check if the request method is PUT
     */
    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    /**
     * Check if the request method is PATCH
     */
    public function isPatch(): bool
    {
        return $this->isMethod('PATCH');
    }

    /**
     * Check if the request method is DELETE
     */
    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Check if the request method is HEAD
     */
    public function isHead(): bool
    {
        return $this->isMethod('HEAD');
    }

    /**
     * Check if the request method is OPTIONS
     */
    public function isOptions(): bool
    {
        return $this->isMethod('OPTIONS');
    }
    
     public function setSession(SessionInterface $session): void
    {
        $this->session = $session;
    }
	
	public function getSession(): SessionInterface
    {
        return $this->session;
    }
    
}

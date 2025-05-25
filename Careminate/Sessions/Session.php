<?php declare (strict_types = 1);
namespace Careminate\Sessions;

use Careminate\Hashes\Hash;

class Session implements SessionInterface
{
    private const FLASH_KEY = 'flash';
    public const AUTH_KEY = 'auth_id';

    public function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Ensure CSRF token exists, else generate it
       // if (!$this->has('csrf_token')) {
        //    $this->set('csrf_token', bin2hex(random_bytes(32))); // set csrf_token in the session
      //  }
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function make(string $key, mixed $value = null): mixed
    {
        if (!is_null($value)) {
            $_SESSION[$key] = Hash::encrypt($value);
        }
        return self::get($key) ? Hash::decrypt($_SESSION[$key]) : '';
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        if (!is_null($value)) {
            $_SESSION[$key] = Hash::encrypt($value);
        }
        $sessionValue = $_SESSION[$key] ?? null;
        self::forget($key);
        return $sessionValue ? Hash::decrypt($sessionValue) : '';
    }

    public function getFlash(string $type): array
    {
        $flash = $this->get(self::FLASH_KEY, []);
        $messages = $flash[$type] ?? [];
        
        if ($messages) {
            unset($flash[$type]);
            $this->set(self::FLASH_KEY, $flash);
        }
        
        return $messages;
    }

    public function setFlash(string $type, string $message): void
    {
        $flash = $this->get(self::FLASH_KEY, []);
        $flash[$type][] = $message;
        $this->set(self::FLASH_KEY, $flash);
    }

    public function hasFlash(string $type): bool
    {
        return isset($_SESSION[self::FLASH_KEY][$type]);
    }

    public function clearFlash(): void
    {
        unset($_SESSION[self::FLASH_KEY]);
    }

    public function auth(): bool
    {
        return $this->has(self::AUTH_KEY);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function forgetAll(): void
    {
        session_destroy();
    }

    public function __destruct()
    {
        session_write_close();
    }
}

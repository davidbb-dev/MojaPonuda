<?php
declare(strict_types=1);

namespace App\Http;

/**
 * Encapsulates PHP session management and provides
 * a consistent interface for storing and retrieving session data.
 *
 * Also provides support for one-time flash messages and
 * complete session cleanup during logout.
 */
class Session{
    public function __construct(){
        if(session_status() === PHP_SESSION_NONE)
        session_start();
    }
    public function get(string $key, mixed $default = null): mixed{
        return $_SESSION[$key] ?? $default;
    }
    public function set(string $key, mixed $value): void{
        $_SESSION[$key] = $value;
    }
    public function allSession(): array{
        return $_SESSION;
    }

     /**
     * Stores a temporary value intended to be consumed
     * by a subsequent request.
     */
    public function setFlash(string $key, mixed $value): void{
        if(!isset($_SESSION['_flash'])){
            $_SESSION['_flash'] = [];
        }
        $_SESSION['_flash'][$key] = $value;
    }

      /**
     * Retrieves a flash value and removes it from the session
     * so it can only be consumed once.
     */
    public function getFlash(string $key, mixed $default = null): mixed{
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
     public function remove(string $key): void{
        unset($_SESSION[$key]);
    }

     /**
     * Completely clears the current session, including
     * session data, the session cookie, and the server-side session.
     */
    public function clear(): void{
        session_unset();
        if(ini_get('session.use_cookies')){
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 86400,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }
}
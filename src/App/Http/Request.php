<?php
declare(strict_types=1);

namespace App\Http;

/**
 * Encapsulates HTTP request data and provides methods
 * for accessing POST, GET, and server variables.
 */
class Request{
    public function __construct(
        private array $post,
        private array $get,
        private array $server
    ){}
    public static function capture(): self{
        return new self($_POST, $_GET, $_SERVER);
    }
    public function method(): string{
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }
    public function post(string $key, mixed $default = null): mixed{
        return $this->post[$key] ?? $default;
    }
    public function get(string $key, mixed $default = null): mixed{
        return $this->get[$key] ?? $default;
    }
    public function input(string $key, mixed $default = null): mixed{
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }
    public function allPost(): mixed{
        return $this->post;
    }
    public function allGet(): mixed{
        return $this->get;
    }
    public function server(string $key, mixed $default = null): mixed{
        return $this->server[$key] ?? $default;
    }
}
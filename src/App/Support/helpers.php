<?php
declare(strict_types=1);

/**
 * Small global helpers. Required once from public/index.php
 * (plain functions are not covered by the class autoloader). 
 */

// if uslovi pre definisanja su za svaki slucaj ako je slucajno helpres.php negde vec uključen

if (!function_exists('e')) {
    /**
     * HTML-escape for safe output. Use everywhere user data is printed.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $to): never
    {
        header('Location: ' . $to);
        exit;
    }
}

if (!function_exists('json_response')) {
    function json_response(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('asset_version')) {
    /**
     * Cache-busting query string for static assets (mtime-based).
     */
    function asset_version(string $relativePath): string
    {
        $full = __DIR__ . '/../../public' . $relativePath;
        return is_file($full) ? (string)filemtime($full) : '1';
    }
}

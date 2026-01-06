<?php
declare(strict_types=1);

namespace App\Helpers;

final class Router {
  private array $routes = [];

  public function get(string $path, callable $handler): void {
    $this->routes['GET'][$this->norm($path)] = $handler;
  }

  public function post(string $path, callable $handler): void {
    $this->routes['POST'][$this->norm($path)] = $handler;
  }

  public function dispatch(string $method, string $uri): void {
    $path = parse_url($uri, PHP_URL_PATH) ?? '/';
    $path = $this->norm($path);
    $handler = $this->routes[$method][$path] ?? null;
    if (!$handler) {
      http_response_code(404);
      require __DIR__ . '/../Views/errors/404.php';
      return;
    }
    $handler();
  }

  private function norm(string $path): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    if ($base !== '' && str_starts_with($path, $base)) {
      $path = substr($path, strlen($base));
      if ($path === '') $path = '/';
    }
    $path = '/' . trim($path, '/');
    if ($path === '/') return '/';
    return $path;
  }
}

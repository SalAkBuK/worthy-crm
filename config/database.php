<?php
declare(strict_types=1);

$env = function ($key, $default = null) {
  return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
};

return [
  'host' => $env('DB_HOST', 'localhost'),
  'name' => $env('DB_NAME', 'wortuckd_attendance'),
  'user' => $env('DB_USER', 'wortuckd_admin'),
  'pass' => $env('DB_PASS', 'Mughees100++'),
  'charset' => 'utf8mb4',
];

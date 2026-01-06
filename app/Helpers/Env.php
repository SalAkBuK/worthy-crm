<?php
declare(strict_types=1);

namespace App\Helpers;

final class Env {
  public static function load(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
      if (str_starts_with(trim($line), '#')) continue;
      if (!str_contains($line, '=')) continue;
      [$k, $v] = array_map('trim', explode('=', $line, 2));
      $v = trim($v, "\"'");
      if ($k && getenv($k) === false) {
        putenv($k . '=' . $v);
        $_ENV[$k] = $v;
      }
    }
  }
}

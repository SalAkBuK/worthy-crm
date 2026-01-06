<?php
declare(strict_types=1);

namespace App\Helpers;

final class Logger {
  public static function info(string $message, array $context = []): void {
    self::write('INFO', $message, $context);
  }

  public static function error(string $message, array $context = []): void {
    self::write('ERROR', $message, $context);
  }

  private static function write(string $level, string $message, array $context): void {
    $line = sprintf("[%s] %s: %s %s\n",
      date('Y-m-d H:i:s'),
      $level,
      $message,
      $context ? json_encode($context, JSON_UNESCAPED_SLASHES) : ''
    );
    $path = __DIR__ . '/../../storage/logs/app.log';
    @file_put_contents($path, $line, FILE_APPEND);
  }
}

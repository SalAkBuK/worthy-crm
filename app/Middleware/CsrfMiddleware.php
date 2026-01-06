<?php
declare(strict_types=1);

namespace App\Middleware;

final class CsrfMiddleware {
  public static function verify(): void {
    \verify_csrf();
  }
}

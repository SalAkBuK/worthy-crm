<?php
declare(strict_types=1);

namespace App\Middleware;

final class AuthMiddleware {
  public static function requireRole(array $roles): void {
    \require_role($roles);
  }
}

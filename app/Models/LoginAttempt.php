<?php
declare(strict_types=1);

namespace App\Models;

use App\Helpers\DB;

final class LoginAttempt {
  public static function recordFailure(string $username, string $ip): void {
    $pdo = DB::conn();
    $st = $pdo->prepare("SELECT * FROM login_attempts WHERE username=:u AND ip_address=:ip LIMIT 1");
    $st->execute([':u'=>$username, ':ip'=>$ip]);
    $row = $st->fetch();

    $attempts = ($row['attempts'] ?? 0) + 1;
    $lockedUntil = $row['locked_until'] ?? null;

    if ($attempts >= 5) {
      $lockedUntil = date('Y-m-d H:i:s', time() + 10 * 60);
      $attempts = 0; // reset counter after lock
    }

    $up = $pdo->prepare("INSERT INTO login_attempts (username, ip_address, attempts, locked_until, last_attempt_at)
      VALUES (:u,:ip,:a,:lu,NOW())
      ON DUPLICATE KEY UPDATE attempts=:a2, locked_until=:lu2, last_attempt_at=NOW()");
    $up->execute([':u'=>$username, ':ip'=>$ip, ':a'=>$attempts, ':lu'=>$lockedUntil, ':a2'=>$attempts, ':lu2'=>$lockedUntil]);
  }

  public static function clear(string $username, string $ip): void {
    $pdo = DB::conn();
    $st = $pdo->prepare("DELETE FROM login_attempts WHERE username=:u AND ip_address=:ip");
    $st->execute([':u'=>$username, ':ip'=>$ip]);
  }

  public static function isLocked(string $username, string $ip): ?string {
    $pdo = DB::conn();
    $st = $pdo->prepare("SELECT locked_until FROM login_attempts WHERE username=:u AND ip_address=:ip LIMIT 1");
    $st->execute([':u'=>$username, ':ip'=>$ip]);
    $locked = $st->fetchColumn();
    if ($locked && strtotime($locked) > time()) {
      return $locked;
    }
    return null;
  }
}

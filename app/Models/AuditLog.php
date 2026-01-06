<?php
declare(strict_types=1);

namespace App\Models;

use App\Helpers\DB;

final class AuditLog {
  public static function log(?int $userId, string $action, array $meta = []): void {
    $pdo = DB::conn();
    $st = $pdo->prepare("INSERT INTO audit_logs (user_id, action, meta_json, ip_address, created_at)
      VALUES (:u,:a,:m,:ip,NOW())");
    $st->execute([
      ':u'=>$userId,
      ':a'=>$action,
      ':m'=>json_encode($meta, JSON_UNESCAPED_SLASHES),
      ':ip'=>($_SERVER['REMOTE_ADDR'] ?? null),
    ]);
  }
}

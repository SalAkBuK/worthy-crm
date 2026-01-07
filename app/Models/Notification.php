<?php
declare(strict_types=1);

namespace App\Models;

use App\Helpers\DB;

final class Notification {
  private static function normalizeTypes(?array $types): ?array {
    if ($types === null) return null;
    $types = array_values(array_filter(array_map('strval', $types), fn($t) => $t !== ''));
    return $types ?: [];
  }

  public static function create(
    int $userId,
    string $type,
    string $title,
    ?string $body = null,
    ?string $linkUrl = null,
    array $meta = [],
    ?string $dedupKey = null
  ): void {
    $pdo = DB::conn();
    $sql = "INSERT INTO notifications (user_id, type, title, body, link_url, meta_json, dedup_key, created_at)
      VALUES (:u,:t,:title,:body,:link,:meta,:dedup,NOW())";
    if ($dedupKey !== null && $dedupKey !== '') {
      $sql = "INSERT IGNORE INTO notifications (user_id, type, title, body, link_url, meta_json, dedup_key, created_at)
        VALUES (:u,:t,:title,:body,:link,:meta,:dedup,NOW())";
    }
    $st = $pdo->prepare($sql);
    $st->execute([
      ':u' => $userId,
      ':t' => $type,
      ':title' => $title,
      ':body' => $body,
      ':link' => $linkUrl,
      ':meta' => $meta ? json_encode($meta, JSON_UNESCAPED_SLASHES) : null,
      ':dedup' => $dedupKey,
    ]);
  }

  public static function createMany(
    array $userIds,
    string $type,
    string $title,
    ?string $body = null,
    ?string $linkUrl = null,
    array $meta = [],
    ?string $dedupKey = null
  ): void {
    $userIds = array_values(array_filter(array_map('intval', $userIds), fn($v) => $v > 0));
    if (!$userIds) return;
    foreach ($userIds as $userId) {
      $key = $dedupKey !== null ? $dedupKey : null;
      self::create($userId, $type, $title, $body, $linkUrl, $meta, $key);
    }
  }

  public static function listForUser(int $userId, int $limit = 50, ?array $types = null): array {
    $types = self::normalizeTypes($types);
    if ($types === []) return [];
    $pdo = DB::conn();
    $sql = "SELECT * FROM notifications WHERE user_id=:u";
    $params = [':u' => $userId];
    if ($types !== null) {
      $placeholders = [];
      foreach ($types as $i => $type) {
        $key = ':t' . $i;
        $placeholders[] = $key;
        $params[$key] = $type;
      }
      $sql .= " AND type IN (" . implode(',', $placeholders) . ")";
    }
    $sql .= " ORDER BY created_at DESC LIMIT :limit";
    $st = $pdo->prepare($sql);
    $st->bindValue(':u', $userId, \PDO::PARAM_INT);
    foreach ($params as $key => $val) {
      if ($key === ':u') continue;
      $st->bindValue($key, $val);
    }
    $st->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public static function unreadCount(int $userId, ?array $types = null): int {
    $types = self::normalizeTypes($types);
    if ($types === []) return 0;
    $pdo = DB::conn();
    $sql = "SELECT COUNT(*) FROM notifications WHERE user_id=:u AND read_at IS NULL";
    $params = [':u' => $userId];
    if ($types !== null) {
      $placeholders = [];
      foreach ($types as $i => $type) {
        $key = ':t' . $i;
        $placeholders[] = $key;
        $params[$key] = $type;
      }
      $sql .= " AND type IN (" . implode(',', $placeholders) . ")";
    }
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return (int)$st->fetchColumn();
  }

  public static function latestAfterId(int $userId, int $lastId, ?array $types = null): ?array {
    $types = self::normalizeTypes($types);
    if ($types === []) return null;
    $pdo = DB::conn();
    $sql = "SELECT * FROM notifications WHERE user_id=:u AND id > :id";
    $params = [':u' => $userId, ':id' => $lastId];
    if ($types !== null) {
      $placeholders = [];
      foreach ($types as $i => $type) {
        $key = ':t' . $i;
        $placeholders[] = $key;
        $params[$key] = $type;
      }
      $sql .= " AND type IN (" . implode(',', $placeholders) . ")";
    }
    $sql .= " ORDER BY id DESC LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function latestIdForUser(int $userId, ?array $types = null): int {
    $types = self::normalizeTypes($types);
    if ($types === []) return 0;
    $pdo = DB::conn();
    $sql = "SELECT COALESCE(MAX(id),0) FROM notifications WHERE user_id=:u";
    $params = [':u' => $userId];
    if ($types !== null) {
      $placeholders = [];
      foreach ($types as $i => $type) {
        $key = ':t' . $i;
        $placeholders[] = $key;
        $params[$key] = $type;
      }
      $sql .= " AND type IN (" . implode(',', $placeholders) . ")";
    }
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return (int)$st->fetchColumn();
  }

  public static function markRead(int $userId, array $ids): int {
    $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
    if (!$ids) return 0;
    $pdo = DB::conn();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "UPDATE notifications SET read_at=NOW() WHERE user_id=? AND id IN ($placeholders)";
    $st = $pdo->prepare($sql);
    $st->execute(array_merge([$userId], $ids));
    return $st->rowCount();
  }

  public static function markAllRead(int $userId): int {
    $pdo = DB::conn();
    $st = $pdo->prepare("UPDATE notifications SET read_at=NOW() WHERE user_id=:u AND read_at IS NULL");
    $st->execute([':u' => $userId]);
    return $st->rowCount();
  }
}

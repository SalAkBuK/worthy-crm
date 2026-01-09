<?php
declare(strict_types=1);

namespace App\Models;

use App\Helpers\DB;

final class ListingDataset {
  private static function nullIfEmpty($value): ?string {
    $value = trim((string)$value);
    return $value === '' ? null : $value;
  }

  private static function intOrNull($value): ?int {
    if ($value === null || $value === '') return null;
    if (!is_numeric($value)) return null;
    return (int)$value;
  }

  public static function findById(int $id): ?array {
    $pdo = DB::conn();
    $st = $pdo->prepare("SELECT * FROM listing_datasets WHERE id=:id LIMIT 1");
    $st->execute([':id' => $id]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function create(array $data): int {
    $pdo = DB::conn();
    $st = $pdo->prepare("INSERT INTO listing_datasets
      (uploaded_by_user_id, original_filename, stored_filename, file_hash, file_size_bytes, mime_type, status,
       parsed_count, failed_count, error_message, extracted_text_path, created_at, updated_at)
      VALUES
      (:user_id, :original, :stored, :hash, :size, :mime, :status, :parsed, :failed, :error, :text_path, NOW(), NOW())");
    $st->execute([
      ':user_id' => self::intOrNull($data['uploaded_by_user_id'] ?? null),
      ':original' => trim((string)($data['original_filename'] ?? '')),
      ':stored' => trim((string)($data['stored_filename'] ?? '')),
      ':hash' => self::nullIfEmpty($data['file_hash'] ?? null),
      ':size' => self::intOrNull($data['file_size_bytes'] ?? null),
      ':mime' => self::nullIfEmpty($data['mime_type'] ?? null),
      ':status' => $data['status'] ?? 'UPLOADED',
      ':parsed' => self::intOrNull($data['parsed_count'] ?? 0) ?? 0,
      ':failed' => self::intOrNull($data['failed_count'] ?? 0) ?? 0,
      ':error' => self::nullIfEmpty($data['error_message'] ?? null),
      ':text_path' => self::nullIfEmpty($data['extracted_text_path'] ?? null),
    ]);
    return (int)$pdo->lastInsertId();
  }

  public static function update(int $id, array $data): bool {
    $fields = [];
    $params = [':id' => $id];

    if (array_key_exists('uploaded_by_user_id', $data)) {
      $fields[] = "uploaded_by_user_id=:user_id";
      $params[':user_id'] = self::intOrNull($data['uploaded_by_user_id']);
    }
    if (array_key_exists('original_filename', $data)) {
      $fields[] = "original_filename=:original";
      $params[':original'] = trim((string)$data['original_filename']);
    }
    if (array_key_exists('stored_filename', $data)) {
      $fields[] = "stored_filename=:stored";
      $params[':stored'] = trim((string)$data['stored_filename']);
    }
    if (array_key_exists('file_hash', $data)) {
      $fields[] = "file_hash=:hash";
      $params[':hash'] = self::nullIfEmpty($data['file_hash']);
    }
    if (array_key_exists('file_size_bytes', $data)) {
      $fields[] = "file_size_bytes=:size";
      $params[':size'] = self::intOrNull($data['file_size_bytes']);
    }
    if (array_key_exists('mime_type', $data)) {
      $fields[] = "mime_type=:mime";
      $params[':mime'] = self::nullIfEmpty($data['mime_type']);
    }
    if (array_key_exists('status', $data)) {
      $fields[] = "status=:status";
      $params[':status'] = $data['status'];
    }
    if (array_key_exists('parsed_count', $data)) {
      $fields[] = "parsed_count=:parsed";
      $params[':parsed'] = self::intOrNull($data['parsed_count']) ?? 0;
    }
    if (array_key_exists('failed_count', $data)) {
      $fields[] = "failed_count=:failed";
      $params[':failed'] = self::intOrNull($data['failed_count']) ?? 0;
    }
    if (array_key_exists('error_message', $data)) {
      $fields[] = "error_message=:error";
      $params[':error'] = self::nullIfEmpty($data['error_message']);
    }
    if (array_key_exists('extracted_text_path', $data)) {
      $fields[] = "extracted_text_path=:text_path";
      $params[':text_path'] = self::nullIfEmpty($data['extracted_text_path']);
    }

    if (!$fields) return false;
    $fields[] = "updated_at=NOW()";

    $sql = "UPDATE listing_datasets SET " . implode(', ', $fields) . " WHERE id=:id";
    $pdo = DB::conn();
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->rowCount() > 0;
  }

  public static function delete(int $id): int {
    $pdo = DB::conn();
    $st = $pdo->prepare("DELETE FROM listing_datasets WHERE id=:id");
    $st->execute([':id' => $id]);
    return $st->rowCount();
  }

  public static function paginate(int $page, int $perPage): array {
    $pdo = DB::conn();
    $count = $pdo->query("SELECT COUNT(*) FROM listing_datasets");
    $total = (int)$count->fetchColumn();
    $meta = \paginate_meta($total, $page, $perPage);

    $st = $pdo->prepare("SELECT * FROM listing_datasets
      ORDER BY created_at DESC
      LIMIT :limit OFFSET :offset");
    $st->bindValue(':limit', $meta['perPage'], \PDO::PARAM_INT);
    $st->bindValue(':offset', $meta['offset'], \PDO::PARAM_INT);
    $st->execute();
    return ['items' => $st->fetchAll(), 'meta' => $meta];
  }
}

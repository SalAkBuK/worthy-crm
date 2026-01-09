<?php
declare(strict_types=1);

namespace App\Models;

use App\Helpers\DB;

final class Listing {
  private static function nullIfEmpty($value): ?string {
    $value = trim((string)$value);
    return $value === '' ? null : $value;
  }

  private static function intOrNull($value): ?int {
    if ($value === null || $value === '') return null;
    if (!is_numeric($value)) return null;
    return (int)$value;
  }

  private static function decimalOrNull($value): ?string {
    if ($value === null || $value === '') return null;
    if (!is_numeric($value)) return null;
    return (string)$value;
  }

  private static function normalizeRawData($value): ?string {
    if ($value === null || $value === '') return null;
    if (is_array($value) || is_object($value)) {
      return json_encode($value, JSON_UNESCAPED_SLASHES);
    }
    $value = trim((string)$value);
    return $value === '' ? null : $value;
  }

  public static function findById(int $id): ?array {
    $pdo = DB::conn();
    $st = $pdo->prepare("SELECT * FROM listings WHERE id=:id LIMIT 1");
    $st->execute([':id' => $id]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function create(array $data): int {
    $pdo = DB::conn();
    $source = $data['source'] ?? 'MANUAL';
    if (!in_array($source, ['MANUAL','PDF'], true)) $source = 'MANUAL';

    $st = $pdo->prepare("INSERT INTO listings
      (project_name, area, developer, unit_ref, property_type, beds_raw, beds, baths_raw, baths,
       size_raw, size_sqft, price_raw, price_amount, status, payment_plan, brochure_url, maps_url,
       media_url, notes, source, dataset_id, raw_data, created_by_user_id, created_at, updated_at)
      VALUES
      (:project_name, :area, :developer, :unit_ref, :property_type, :beds_raw, :beds, :baths_raw, :baths,
       :size_raw, :size_sqft, :price_raw, :price_amount, :status, :payment_plan, :brochure_url, :maps_url,
       :media_url, :notes, :source, :dataset_id, :raw_data, :created_by, NOW(), NOW())");
    $st->execute([
      ':project_name' => trim((string)($data['project_name'] ?? '')),
      ':area' => trim((string)($data['area'] ?? '')),
      ':developer' => self::nullIfEmpty($data['developer'] ?? null),
      ':unit_ref' => self::nullIfEmpty($data['unit_ref'] ?? null),
      ':property_type' => self::nullIfEmpty($data['property_type'] ?? null),
      ':beds_raw' => self::nullIfEmpty($data['beds_raw'] ?? null),
      ':beds' => self::intOrNull($data['beds'] ?? null),
      ':baths_raw' => self::nullIfEmpty($data['baths_raw'] ?? null),
      ':baths' => self::intOrNull($data['baths'] ?? null),
      ':size_raw' => self::nullIfEmpty($data['size_raw'] ?? null),
      ':size_sqft' => self::decimalOrNull($data['size_sqft'] ?? null),
      ':price_raw' => self::nullIfEmpty($data['price_raw'] ?? null),
      ':price_amount' => self::decimalOrNull($data['price_amount'] ?? null),
      ':status' => self::nullIfEmpty($data['status'] ?? null),
      ':payment_plan' => self::nullIfEmpty($data['payment_plan'] ?? null),
      ':brochure_url' => self::nullIfEmpty($data['brochure_url'] ?? null),
      ':maps_url' => self::nullIfEmpty($data['maps_url'] ?? null),
      ':media_url' => self::nullIfEmpty($data['media_url'] ?? null),
      ':notes' => self::nullIfEmpty($data['notes'] ?? null),
      ':source' => $source,
      ':dataset_id' => self::intOrNull($data['dataset_id'] ?? null),
      ':raw_data' => self::normalizeRawData($data['raw_data'] ?? null),
      ':created_by' => self::intOrNull($data['created_by_user_id'] ?? null),
    ]);
    return (int)$pdo->lastInsertId();
  }

  public static function update(int $id, array $data): bool {
    $fields = [];
    $params = [':id' => $id];

    if (array_key_exists('project_name', $data)) {
      $fields[] = "project_name=:project_name";
      $params[':project_name'] = trim((string)$data['project_name']);
    }
    if (array_key_exists('area', $data)) {
      $fields[] = "area=:area";
      $params[':area'] = trim((string)$data['area']);
    }
    if (array_key_exists('developer', $data)) {
      $fields[] = "developer=:developer";
      $params[':developer'] = self::nullIfEmpty($data['developer']);
    }
    if (array_key_exists('unit_ref', $data)) {
      $fields[] = "unit_ref=:unit_ref";
      $params[':unit_ref'] = self::nullIfEmpty($data['unit_ref']);
    }
    if (array_key_exists('property_type', $data)) {
      $fields[] = "property_type=:property_type";
      $params[':property_type'] = self::nullIfEmpty($data['property_type']);
    }
    if (array_key_exists('beds_raw', $data)) {
      $fields[] = "beds_raw=:beds_raw";
      $params[':beds_raw'] = self::nullIfEmpty($data['beds_raw']);
    }
    if (array_key_exists('beds', $data)) {
      $fields[] = "beds=:beds";
      $params[':beds'] = self::intOrNull($data['beds']);
    }
    if (array_key_exists('baths_raw', $data)) {
      $fields[] = "baths_raw=:baths_raw";
      $params[':baths_raw'] = self::nullIfEmpty($data['baths_raw']);
    }
    if (array_key_exists('baths', $data)) {
      $fields[] = "baths=:baths";
      $params[':baths'] = self::intOrNull($data['baths']);
    }
    if (array_key_exists('size_raw', $data)) {
      $fields[] = "size_raw=:size_raw";
      $params[':size_raw'] = self::nullIfEmpty($data['size_raw']);
    }
    if (array_key_exists('size_sqft', $data)) {
      $fields[] = "size_sqft=:size_sqft";
      $params[':size_sqft'] = self::decimalOrNull($data['size_sqft']);
    }
    if (array_key_exists('price_raw', $data)) {
      $fields[] = "price_raw=:price_raw";
      $params[':price_raw'] = self::nullIfEmpty($data['price_raw']);
    }
    if (array_key_exists('price_amount', $data)) {
      $fields[] = "price_amount=:price_amount";
      $params[':price_amount'] = self::decimalOrNull($data['price_amount']);
    }
    if (array_key_exists('status', $data)) {
      $fields[] = "status=:status";
      $params[':status'] = self::nullIfEmpty($data['status']);
    }
    if (array_key_exists('payment_plan', $data)) {
      $fields[] = "payment_plan=:payment_plan";
      $params[':payment_plan'] = self::nullIfEmpty($data['payment_plan']);
    }
    if (array_key_exists('brochure_url', $data)) {
      $fields[] = "brochure_url=:brochure_url";
      $params[':brochure_url'] = self::nullIfEmpty($data['brochure_url']);
    }
    if (array_key_exists('maps_url', $data)) {
      $fields[] = "maps_url=:maps_url";
      $params[':maps_url'] = self::nullIfEmpty($data['maps_url']);
    }
    if (array_key_exists('media_url', $data)) {
      $fields[] = "media_url=:media_url";
      $params[':media_url'] = self::nullIfEmpty($data['media_url']);
    }
    if (array_key_exists('notes', $data)) {
      $fields[] = "notes=:notes";
      $params[':notes'] = self::nullIfEmpty($data['notes']);
    }
    if (array_key_exists('source', $data)) {
      $source = $data['source'];
      if (!in_array($source, ['MANUAL','PDF'], true)) $source = 'MANUAL';
      $fields[] = "source=:source";
      $params[':source'] = $source;
    }
    if (array_key_exists('dataset_id', $data)) {
      $fields[] = "dataset_id=:dataset_id";
      $params[':dataset_id'] = self::intOrNull($data['dataset_id']);
    }
    if (array_key_exists('raw_data', $data)) {
      $fields[] = "raw_data=:raw_data";
      $params[':raw_data'] = self::normalizeRawData($data['raw_data']);
    }
    if (array_key_exists('created_by_user_id', $data)) {
      $fields[] = "created_by_user_id=:created_by";
      $params[':created_by'] = self::intOrNull($data['created_by_user_id']);
    }

    if (!$fields) return false;
    $fields[] = "updated_at=NOW()";

    $sql = "UPDATE listings SET " . implode(', ', $fields) . " WHERE id=:id";
    $pdo = DB::conn();
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->rowCount() > 0;
  }

  public static function delete(int $id): int {
    $pdo = DB::conn();
    $st = $pdo->prepare("DELETE FROM listings WHERE id=:id");
    $st->execute([':id' => $id]);
    return $st->rowCount();
  }

  public static function search(array $filters, int $page, int $perPage): array {
    $pdo = DB::conn();
    $where = [];
    $params = [];

    if (!empty($filters['q'])) {
      $where[] = "(l.project_name LIKE :q_project OR l.area LIKE :q_area OR l.developer LIKE :q_developer OR l.unit_ref LIKE :q_unit)";
      $q = '%' . $filters['q'] . '%';
      $params[':q_project'] = $q;
      $params[':q_area'] = $q;
      $params[':q_developer'] = $q;
      $params[':q_unit'] = $q;
    }
    if (!empty($filters['project_name'])) {
      $where[] = "l.project_name LIKE :project_name";
      $params[':project_name'] = '%' . $filters['project_name'] . '%';
    }
    if (!empty($filters['area'])) {
      $where[] = "l.area LIKE :area";
      $params[':area'] = '%' . $filters['area'] . '%';
    }
    if (!empty($filters['developer'])) {
      $where[] = "l.developer LIKE :developer";
      $params[':developer'] = '%' . $filters['developer'] . '%';
    }
    if (!empty($filters['property_type'])) {
      $where[] = "l.property_type LIKE :property_type";
      $params[':property_type'] = '%' . $filters['property_type'] . '%';
    }
    if (!empty($filters['bedrooms'])) {
      $where[] = "(l.beds_raw LIKE :beds_raw" . (is_numeric($filters['bedrooms']) ? " OR l.beds = :beds_int" : "") . ")";
      $params[':beds_raw'] = '%' . $filters['bedrooms'] . '%';
      if (is_numeric($filters['bedrooms'])) {
        $params[':beds_int'] = (int)$filters['bedrooms'];
      }
    }
    if (!empty($filters['status'])) {
      $where[] = "l.status = :status";
      $params[':status'] = $filters['status'];
    }
    if (!empty($filters['source'])) {
      $source = $filters['source'];
      if (in_array($source, ['MANUAL','PDF'], true)) {
        $where[] = "l.source = :source";
        $params[':source'] = $source;
      }
    }
    if (!empty($filters['dataset_id'])) {
      $where[] = "l.dataset_id = :dataset_id";
      $params[':dataset_id'] = (int)$filters['dataset_id'];
    }
    if (!empty($filters['created_by_user_id'])) {
      $where[] = "l.created_by_user_id = :created_by";
      $params[':created_by'] = (int)$filters['created_by_user_id'];
    }
    if (!empty($filters['from'])) {
      $where[] = "DATE(l.created_at) >= :from";
      $params[':from'] = $filters['from'];
    }
    if (!empty($filters['to'])) {
      $where[] = "DATE(l.created_at) <= :to";
      $params[':to'] = $filters['to'];
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $allowedSort = ['created_at','project_name','area','price_amount','status'];
    $sortCandidate = $filters['sort'] ?? 'created_at';
    $sort = in_array($sortCandidate, $allowedSort, true) ? $sortCandidate : 'created_at';
    $dir = (($filters['dir'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';

    $count = $pdo->prepare("SELECT COUNT(*) FROM listings l $whereSql");
    $count->execute($params);
    $total = (int)$count->fetchColumn();
    $meta = \paginate_meta($total, $page, $perPage);

    $sql = "SELECT l.* FROM listings l
      $whereSql
      ORDER BY l.$sort $dir
      LIMIT :limit OFFSET :offset";
    $st = $pdo->prepare($sql);
    foreach ($params as $k => $v) $st->bindValue($k, $v);
    $st->bindValue(':limit', $meta['perPage'], \PDO::PARAM_INT);
    $st->bindValue(':offset', $meta['offset'], \PDO::PARAM_INT);
    $st->execute();
    return ['items' => $st->fetchAll(), 'meta' => $meta];
  }
}

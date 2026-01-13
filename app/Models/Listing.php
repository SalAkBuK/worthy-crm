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

  private static function normalizeDetailsJson($value): ?string {
    if ($value === null || $value === '') return null;
    if (is_array($value) || is_object($value)) {
      return json_encode($value, JSON_UNESCAPED_SLASHES);
    }
    $value = trim((string)$value);
    return $value === '' ? null : $value;
  }

  public static function resolvePriceData(array $data): array {
    $priceRaw = $data['price_raw'] ?? null;
    $priceAmount = $data['price_amount'] ?? null;
    $furnishedRaw = $data['price_furnished_raw'] ?? null;
    $unfurnishedRaw = $data['price_unfurnished_raw'] ?? null;
    $furnishedAmount = $data['price_furnished_amount'] ?? null;
    $unfurnishedAmount = $data['price_unfurnished_amount'] ?? null;

    if ($furnishedAmount === '') $furnishedAmount = null;
    if ($unfurnishedAmount === '') $unfurnishedAmount = null;
    if ($priceAmount === '') $priceAmount = null;

    if ($furnishedAmount !== null && !is_numeric($furnishedAmount)) {
      $furnishedAmount = \parse_aed_amount((string)$furnishedAmount);
    }
    if ($unfurnishedAmount !== null && !is_numeric($unfurnishedAmount)) {
      $unfurnishedAmount = \parse_aed_amount((string)$unfurnishedAmount);
    }
    if ($priceAmount !== null && !is_numeric($priceAmount)) {
      $priceAmount = \parse_aed_amount((string)$priceAmount);
    }

    if ($furnishedAmount === null && $furnishedRaw !== null && $furnishedRaw !== '') {
      $furnishedAmount = \parse_aed_amount((string)$furnishedRaw);
    }
    if ($unfurnishedAmount === null && $unfurnishedRaw !== null && $unfurnishedRaw !== '') {
      $unfurnishedAmount = \parse_aed_amount((string)$unfurnishedRaw);
    }
    if ($priceAmount === null && $priceRaw !== null && $priceRaw !== '') {
      $priceAmount = \parse_aed_amount((string)$priceRaw);
    }

    $displayType = $data['price_display_type'] ?? null;
    $displayLabel = $data['price_display_label'] ?? null;

    if ($furnishedAmount !== null || $unfurnishedAmount !== null) {
      if ($furnishedAmount !== null) {
        $priceAmount = $furnishedAmount;
        $displayType = 'FURNISHED';
        $displayLabel = 'Furnished';
      } else {
        $priceAmount = $unfurnishedAmount;
        $displayType = 'UNFURNISHED';
        $displayLabel = 'Unfurnished';
      }
    } elseif ($priceAmount !== null) {
      $displayType = 'SINGLE';
      $displayLabel = 'Price';
    }

    if ($displayType === null || $displayType === '') $displayType = 'SINGLE';
    if ($displayLabel === null || $displayLabel === '') $displayLabel = 'Price';

    $data['price_amount'] = $priceAmount;
    $data['price_furnished_amount'] = $furnishedAmount;
    $data['price_unfurnished_amount'] = $unfurnishedAmount;
    $data['price_display_type'] = $displayType;
    $data['price_display_label'] = $displayLabel;

    return $data;
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
       size_raw, size_sqft, price_raw, price_amount, price_furnished_raw, price_unfurnished_raw,
       price_furnished_amount, price_unfurnished_amount, price_display_type, price_display_label,
       listing_type, status, payment_plan, brochure_url, maps_url, media_url, notes, details_json,
       source, dataset_id, raw_data, created_by_user_id, created_at, updated_at)
      VALUES
      (:project_name, :area, :developer, :unit_ref, :property_type, :beds_raw, :beds, :baths_raw, :baths,
       :size_raw, :size_sqft, :price_raw, :price_amount, :price_furnished_raw, :price_unfurnished_raw,
       :price_furnished_amount, :price_unfurnished_amount, :price_display_type, :price_display_label,
       :listing_type, :status, :payment_plan, :brochure_url, :maps_url, :media_url, :notes, :details_json,
       :source, :dataset_id, :raw_data, :created_by, NOW(), NOW())");
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
      ':price_furnished_raw' => self::nullIfEmpty($data['price_furnished_raw'] ?? null),
      ':price_unfurnished_raw' => self::nullIfEmpty($data['price_unfurnished_raw'] ?? null),
      ':price_furnished_amount' => self::decimalOrNull($data['price_furnished_amount'] ?? null),
      ':price_unfurnished_amount' => self::decimalOrNull($data['price_unfurnished_amount'] ?? null),
      ':price_display_type' => self::nullIfEmpty($data['price_display_type'] ?? null),
      ':price_display_label' => self::nullIfEmpty($data['price_display_label'] ?? null),
      ':listing_type' => self::nullIfEmpty($data['listing_type'] ?? null),
      ':status' => self::nullIfEmpty($data['status'] ?? null),
      ':payment_plan' => self::nullIfEmpty($data['payment_plan'] ?? null),
      ':brochure_url' => self::nullIfEmpty($data['brochure_url'] ?? null),
      ':maps_url' => self::nullIfEmpty($data['maps_url'] ?? null),
      ':media_url' => self::nullIfEmpty($data['media_url'] ?? null),
      ':notes' => self::nullIfEmpty($data['notes'] ?? null),
      ':details_json' => self::normalizeDetailsJson($data['details_json'] ?? null),
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
    if (array_key_exists('price_furnished_raw', $data)) {
      $fields[] = "price_furnished_raw=:price_furnished_raw";
      $params[':price_furnished_raw'] = self::nullIfEmpty($data['price_furnished_raw']);
    }
    if (array_key_exists('price_unfurnished_raw', $data)) {
      $fields[] = "price_unfurnished_raw=:price_unfurnished_raw";
      $params[':price_unfurnished_raw'] = self::nullIfEmpty($data['price_unfurnished_raw']);
    }
    if (array_key_exists('price_furnished_amount', $data)) {
      $fields[] = "price_furnished_amount=:price_furnished_amount";
      $params[':price_furnished_amount'] = self::decimalOrNull($data['price_furnished_amount']);
    }
    if (array_key_exists('price_unfurnished_amount', $data)) {
      $fields[] = "price_unfurnished_amount=:price_unfurnished_amount";
      $params[':price_unfurnished_amount'] = self::decimalOrNull($data['price_unfurnished_amount']);
    }
    if (array_key_exists('price_display_type', $data)) {
      $fields[] = "price_display_type=:price_display_type";
      $params[':price_display_type'] = self::nullIfEmpty($data['price_display_type']);
    }
    if (array_key_exists('price_display_label', $data)) {
      $fields[] = "price_display_label=:price_display_label";
      $params[':price_display_label'] = self::nullIfEmpty($data['price_display_label']);
    }
    if (array_key_exists('listing_type', $data)) {
      $fields[] = "listing_type=:listing_type";
      $params[':listing_type'] = self::nullIfEmpty($data['listing_type']);
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
    if (array_key_exists('details_json', $data)) {
      $fields[] = "details_json=:details_json";
      $params[':details_json'] = self::normalizeDetailsJson($data['details_json']);
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

  public static function getFilterOptions(): array {
    $pdo = DB::conn();
    $options = [
      'developers' => [],
      'statuses' => [],
      'property_types' => [],
      'bedrooms' => [],
      'projects' => [],
    ];

    $queries = [
      'developers' => "SELECT DISTINCT developer AS value FROM listings WHERE developer IS NOT NULL AND TRIM(developer) <> '' ORDER BY developer",
      'statuses' => "SELECT DISTINCT status AS value FROM listings WHERE status IS NOT NULL AND TRIM(status) <> '' ORDER BY status",
      'property_types' => "SELECT DISTINCT property_type AS value FROM listings WHERE property_type IS NOT NULL AND TRIM(property_type) <> '' ORDER BY property_type",
      'projects' => "SELECT DISTINCT project_name AS value FROM listings WHERE project_name IS NOT NULL AND TRIM(project_name) <> '' ORDER BY project_name",
    ];

    foreach ($queries as $key => $sql) {
      $st = $pdo->prepare($sql);
      $st->execute();
      $options[$key] = array_values(array_filter(array_map(static function ($row) {
        $value = $row['value'] ?? '';
        $value = trim((string)$value);
        return $value === '' ? null : $value;
      }, $st->fetchAll()), static function ($value) {
        return $value !== null;
      }));
    }

    $bedroomValues = [];
    $st = $pdo->prepare("SELECT DISTINCT beds_raw AS value FROM listings WHERE beds_raw IS NOT NULL AND TRIM(beds_raw) <> '' ORDER BY beds_raw");
    $st->execute();
    foreach ($st->fetchAll() as $row) {
      $value = trim((string)($row['value'] ?? ''));
      if ($value === '') continue;
      $label = null;
      if (stripos($value, 'studio') !== false) {
        $label = 'Studio';
      } elseif (preg_match('/\d+/', $value, $m)) {
        $label = ((int)$m[0]) . ' BDR';
      }
      if ($label !== null) {
        $bedroomValues[$label] = true;
      }
    }
    $st = $pdo->prepare("SELECT DISTINCT beds AS value FROM listings WHERE beds IS NOT NULL ORDER BY beds");
    $st->execute();
    foreach ($st->fetchAll() as $row) {
      $value = (string)($row['value'] ?? '');
      $value = trim($value);
      if ($value !== '') {
        $bedroomValues[((int)$value) . ' BDR'] = true;
      }
    }
    $options['bedrooms'] = array_keys($bedroomValues);
    if ($options['bedrooms']) {
      usort($options['bedrooms'], static function ($a, $b) {
        $aLower = strtolower($a);
        $bLower = strtolower($b);
        if ($aLower === 'studio' && $bLower !== 'studio') return -1;
        if ($bLower === 'studio' && $aLower !== 'studio') return 1;
        $aNum = preg_match('/\d+/', $a, $am) ? (int)$am[0] : 0;
        $bNum = preg_match('/\d+/', $b, $bm) ? (int)$bm[0] : 0;
        if ($aNum === $bNum) return strcasecmp($a, $b);
        return $aNum <=> $bNum;
      });
    }

    return $options;
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
      $bedrooms = trim((string)$filters['bedrooms']);
      if (strcasecmp($bedrooms, 'Studio') === 0) {
        $where[] = "(LOWER(l.beds_raw) LIKE :beds_raw OR l.beds = 0)";
        $params[':beds_raw'] = '%studio%';
      } elseif (preg_match('/^(\\d+)\\s*BDR$/i', $bedrooms, $m)) {
        $bedsInt = (int)$m[1];
        $where[] = "(l.beds = :beds_int OR l.beds_raw REGEXP :beds_regex)";
        $params[':beds_int'] = $bedsInt;
        $params[':beds_regex'] = '(^|[^0-9])' . $bedsInt . '([^0-9]|$)';
      } else {
        $where[] = "l.beds_raw LIKE :beds_raw";
        $params[':beds_raw'] = '%' . $bedrooms . '%';
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

    $orderExpr = "l.$sort";
    if ($sort === 'price_amount') {
      $rawPriceExpr = "NULLIF(REPLACE(REPLACE(REPLACE(LOWER(l.price_raw),'aed',''),',',''),' ',''), '')";
      $orderExpr = "COALESCE(l.price_amount, CAST($rawPriceExpr AS DECIMAL(14,2)))";
    }

    $sql = "SELECT l.* FROM listings l
      $whereSql
      ORDER BY $orderExpr $dir
      LIMIT :limit OFFSET :offset";
    $st = $pdo->prepare($sql);
    foreach ($params as $k => $v) $st->bindValue($k, $v);
    $st->bindValue(':limit', $meta['perPage'], \PDO::PARAM_INT);
    $st->bindValue(':offset', $meta['offset'], \PDO::PARAM_INT);
    $st->execute();
    return ['items' => $st->fetchAll(), 'meta' => $meta];
  }
}

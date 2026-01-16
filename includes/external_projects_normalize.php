<?php
declare(strict_types=1);

function normalize_external_project(array $raw): array {
  $id = (int)($raw['id'] ?? ($raw['project_id'] ?? 0));
  $slug = (string)($raw['slug'] ?? '');
  $title = (string)($raw['title'] ?? ($raw['name'] ?? ''));
  $type = (string)($raw['types'] ?? '');
  if ($type === '' && !empty($raw['property_types']) && is_array($raw['property_types'])) {
    $type = (string)($raw['property_types'][0] ?? '');
  }
  $status = (string)($raw['project_status'] ?? ($raw['status'] ?? ''));

  $city = '';
  if (!empty($raw['city_data']['name'])) {
    $city = (string)$raw['city_data']['name'];
  } elseif (!empty($raw['project_city'])) {
    $city = (string)$raw['project_city'];
  }

  $district = '';
  if (!empty($raw['district_data'])) {
    if (is_array($raw['district_data'])) {
      $first = $raw['district_data'][0] ?? null;
      if (is_array($first) && !empty($first['name'])) {
        $district = (string)$first['name'];
      } elseif (!empty($raw['district_data']['name'])) {
        $district = (string)$raw['district_data']['name'];
      }
    }
  }
  if ($district === '' && !empty($raw['district'])) {
    $district = (string)$raw['district'];
  }

  $completionDate = (string)($raw['expected_completion_date'] ?? ($raw['handover_date'] ?? ''));
  $completionYear = null;
  $completionQuarter = null;
  if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $completionDate, $m)) {
    $completionYear = (int)$m[1];
    $month = (int)$m[2];
    $completionQuarter = 'Q' . (int)ceil($month / 3);
  }

  $lat = null;
  $lng = null;
  $latLng = (string)($raw['latlong'] ?? ($raw['lat_lng'] ?? ''));
  if ($latLng !== '' && preg_match('/^\s*([\d.\-]+)\s*,\s*([\d.\-]+)\s*$/', $latLng, $m)) {
    $lat = (float)$m[1];
    $lng = (float)$m[2];
  }

  $images = [];
  if (!empty($raw['all_images']) && is_array($raw['all_images'])) {
    $images = array_filter($raw['all_images'], 'is_string');
  } elseif (!empty($raw['images']) && is_array($raw['images'])) {
    if (isset($raw['images']['feature'])) {
      if (!empty($raw['images']['feature'])) $images[] = (string)$raw['images']['feature'];
      if (!empty($raw['images']['other']) && is_array($raw['images']['other'])) {
        foreach ($raw['images']['other'] as $img) {
          if (is_string($img)) $images[] = $img;
        }
      }
    } else {
      foreach ($raw['images'] as $img) {
        if (is_string($img)) $images[] = $img;
        if (is_array($img) && !empty($img['url'])) $images[] = (string)$img['url'];
      }
    }
  }
  $images = array_values(array_unique(array_filter($images, function ($img): bool {
    return is_string($img) && $img !== '';
  })));
  $imagesCount = count($images);

  $image = '';
  if (!empty($raw['banner_image'])) {
    $image = (string)$raw['banner_image'];
  } elseif (!empty($raw['image'])) {
    $image = (string)$raw['image'];
  } elseif ($imagesCount > 0) {
    $image = (string)$images[0];
  }

  $facilities = [];
  if (!empty($raw['facilities']) && is_array($raw['facilities'])) {
    $facilities = $raw['facilities'];
  }
  $facilitiesCount = count($facilities);

  $hasPaymentPlan = false;
  if (array_key_exists('payment_plans_json', $raw)) {
    $hasPaymentPlan = $raw['payment_plans_json'] !== null;
  }
  if (!empty($raw['new_payment_plans']) && is_array($raw['new_payment_plans'])) {
    $hasPaymentPlan = true;
  }

  $priceStart = normalize_money_to_int($raw['price_start'] ?? null);
  if ($priceStart === null || $priceStart === 0) {
    $priceStart = normalize_money_to_int($raw['up_front_price'] ?? ($raw['price'] ?? null));
  }
  $descriptionText = normalize_description_text($raw['description'] ?? null);
  if (($priceStart === null || $priceStart === 0) && $descriptionText !== '') {
    $priceStart = parse_price_from_text($descriptionText);
  }
  $priceIsTba = ($priceStart === null || $priceStart === 0);
  if ($priceStart === 0) $priceStart = null;

  $bedsMin = null;
  $bedsMax = null;
  if (!empty($raw['beds'])) {
    if (is_array($raw['beds'])) {
      $bedsValues = array_values(array_filter(array_map('normalize_int', $raw['beds']), 'is_int'));
      if ($bedsValues) {
        $bedsMin = min($bedsValues);
        $bedsMax = max($bedsValues);
      }
    } else {
      $bedsMin = normalize_int($raw['beds']);
      $bedsMax = $bedsMin;
    }
  }
  if (($bedsMin === null || $bedsMax === null) && !empty($raw['typical_units']) && is_array($raw['typical_units'])) {
    $bedsValues = [];
    foreach ($raw['typical_units'] as $unit) {
      if (!is_array($unit)) continue;
      $bed = normalize_int($unit['bedroom'] ?? null);
      if ($bed !== null) $bedsValues[] = $bed;
    }
    if ($bedsValues) {
      $bedsMin = min($bedsValues);
      $bedsMax = max($bedsValues);
    }
  }
  if (($bedsMin === null || $bedsMax === null) && $descriptionText !== '') {
    $bedRange = parse_beds_from_text($descriptionText);
    if ($bedRange) {
      $bedsMin = $bedRange['min'];
      $bedsMax = $bedRange['max'];
    }
  }
  $bedsIsTba = ($bedsMin === null || $bedsMax === null);

  $sizeMin = normalize_int($raw['area_start'] ?? null);
  $sizeMax = normalize_int($raw['area_end'] ?? null);
  if (($sizeMin === null && $sizeMax === null) && !empty($raw['typical_units']) && is_array($raw['typical_units'])) {
    $sizes = [];
    foreach ($raw['typical_units'] as $unit) {
      if (!is_array($unit)) continue;
      $low = normalize_int($unit['lowest_area'] ?? null);
      $high = normalize_int($unit['highest_area'] ?? null);
      if ($low !== null) $sizes[] = $low;
      if ($high !== null) $sizes[] = $high;
    }
    if ($sizes) {
      $sizeMin = min($sizes);
      $sizeMax = max($sizes);
    }
  }
  if (($sizeMin === null && $sizeMax === null) && $descriptionText !== '') {
    $sizeRange = parse_size_from_text($descriptionText);
    if ($sizeRange) {
      $sizeMin = $sizeRange['min'];
      $sizeMax = $sizeRange['max'];
    }
  }
  $sizeIsTba = ($sizeMin === null || $sizeMax === null);

  $createdAt = (string)($raw['created_at'] ?? '');
  $isCompleted = compute_project_is_completed($raw);

  return [
    'id' => $id,
    'slug' => $slug,
    'title' => $title,
    'type' => $type,
    'status' => $status,
    'city' => $city,
    'district' => $district,
    'completion_date' => $completionDate,
    'completion_year' => $completionYear,
    'completion_quarter' => $completionQuarter,
    'lat' => $lat,
    'lng' => $lng,
    'has_gallery' => $imagesCount > 0,
    'images_count' => $imagesCount,
    'has_facilities' => $facilitiesCount > 0,
    'facilities_count' => $facilitiesCount,
    'has_payment_plan' => $hasPaymentPlan,
    'price_start_aed' => $priceStart,
    'price_is_tba' => $priceIsTba,
    'beds_min' => $bedsMin,
    'beds_max' => $bedsMax,
    'beds_is_tba' => $bedsIsTba,
    'size_min_sqft' => $sizeMin,
    'size_max_sqft' => $sizeMax,
    'size_is_tba' => $sizeIsTba,
    'image' => $image,
    'created_at' => $createdAt,
    'is_completed' => $isCompleted,
  ];
}


// Behavior examples:
// - project_completed=true => true
// - expected_completion_date <= today (valid, not 1970-01-01) => true
// - empty/invalid/1970-01-01 => false
function compute_project_is_completed(array $project): bool {
  if (!empty($project['project_completed'])) {
    return true;
  }
  $rawDate = $project['expected_completion_date'] ?? null;
  if (!is_string($rawDate)) {
    return false;
  }
  $rawDate = trim($rawDate);
  if ($rawDate === '' || $rawDate == '1970-01-01') {
    return false;
  }
  $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $rawDate);
  $errors = DateTimeImmutable::getLastErrors();
  if ($dt === false || ($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0) {
    return false;
  }
  $today = new DateTimeImmutable('today');
  return $dt <= $today;
}

function normalize_description_text($raw): string {
  if (!is_string($raw) || $raw === '') return '';
  $text = strip_tags($raw);
  $text = html_entity_decode($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  $text = preg_replace('/\s+/', ' ', $text);
  return trim((string)$text);
}

function normalize_int($value): ?int {
  if ($value === null) return null;
  if (is_int($value)) return $value;
  if (is_float($value)) return (int)round($value);
  $str = trim((string)$value);
  if ($str === '' || $str === '0') return null;
  if (!preg_match('/^-?\d+$/', str_replace([',', ' '], '', $str))) return null;
  return (int)str_replace([',', ' '], '', $str);
}

function normalize_money_to_int($value): ?int {
  if ($value === null) return null;
  $str = trim((string)$value);
  if ($str === '' || $str === '0' || $str === '0.00') return null;
  $digits = preg_replace('/[^0-9.]/', '', $str);
  if ($digits === '') return null;
  $num = (float)$digits;
  if ($num <= 0) return null;
  return (int)round($num);
}

function parse_price_from_text(string $text): ?int {
  $text = strtoupper($text);
  if (preg_match('/AED\s*([\d,.]+)\s*(M|MILLION)?/', $text, $m)) {
    $num = (float)str_replace(',', '', $m[1]);
    $mult = (!empty($m[2]) ? 1000000 : 1);
    $value = (int)round($num * $mult);
    return $value > 0 ? $value : null;
  }
  if (preg_match('/([\d,.]+)\s*(M|MILLION)\s*AED/', $text, $m)) {
    $num = (float)str_replace(',', '', $m[1]);
    $value = (int)round($num * 1000000);
    return $value > 0 ? $value : null;
  }
  if (preg_match('/AED\s*([\d,]{4,})/', $text, $m)) {
    $num = (int)str_replace(',', '', $m[1]);
    return $num > 0 ? $num : null;
  }
  return null;
}

function parse_size_from_text(string $text): ?array {
  if (preg_match('/([\d,]+)\s*(?:to|\-|–)\s*([\d,]+)\s*(sq\.?\s*ft|sqft|sq\.?\s*feet)/i', $text, $m)) {
    $min = (int)str_replace(',', '', $m[1]);
    $max = (int)str_replace(',', '', $m[2]);
    return ['min' => $min, 'max' => $max];
  }
  return null;
}

function parse_beds_from_text(string $text): ?array {
  if (preg_match('/(\d+(?:\s*(?:,|&|and|\-)\s*\d+)+)\s*(?:bed|bedroom|br)/i', $text, $m)) {
    preg_match_all('/\d+/', $m[1], $nums);
    $vals = array_map('intval', $nums[0]);
    if ($vals) {
      return ['min' => min($vals), 'max' => max($vals)];
    }
  }
  if (preg_match('/(\d+)\s*(?:bed|bedroom|br)/i', $text, $m)) {
    $val = (int)$m[1];
    return ['min' => $val, 'max' => $val];
  }
  return null;
}

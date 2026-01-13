<?php
declare(strict_types=1);

require_once __DIR__ . '/../init.php';

use App\Models\Listing;

$cases = [
  [
    'name' => 'both_prices',
    'input' => [
      'price_furnished_raw' => 'AED 1,365,000',
      'price_unfurnished_raw' => 'AED 1,250,000',
    ],
    'expect' => [
      'price_amount' => 1365000,
      'price_display_label' => 'Furnished',
      'price_display_type' => 'FURNISHED',
    ],
  ],
  [
    'name' => 'only_unfurnished',
    'input' => [
      'price_unfurnished_raw' => '1 955 370 AED',
    ],
    'expect' => [
      'price_amount' => 1955370,
      'price_display_label' => 'Unfurnished',
      'price_display_type' => 'UNFURNISHED',
    ],
  ],
  [
    'name' => 'single_price',
    'input' => [
      'price_raw' => 'AED 900,000',
    ],
    'expect' => [
      'price_amount' => 900000,
      'price_display_label' => 'Price',
      'price_display_type' => 'SINGLE',
    ],
  ],
];

$failures = 0;
foreach ($cases as $case) {
  $result = Listing::resolvePriceData($case['input']);
  foreach ($case['expect'] as $key => $expected) {
    $got = $result[$key] ?? null;
    if ($got !== $expected) {
      $failures++;
      echo "FAIL {$case['name']} {$key}: expected {$expected}, got {$got}\n";
    }
  }
}

if ($failures === 0) {
  echo "OK: price resolution sanity checks passed.\n";
  exit(0);
}

exit(1);

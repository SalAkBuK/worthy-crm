<?php
declare(strict_types=1);

function parsePriceToInt(?string $price): ?int {
  if ($price === null) return null;
  $price = trim($price);
  if ($price === '') return null;
  $digits = preg_replace('/\D+/', '', $price);
  if ($digits === '') return null;
  return (int)$digits;
}

function handoverSortKey(?string $handover): ?int {
  if ($handover === null) return null;
  $handover = trim($handover);
  if ($handover === '') return null;
  if (!preg_match('/^Q([1-4])\s+(\d{4})$/i', $handover, $m)) {
    return null;
  }
  $quarter = (int)$m[1];
  $year = (int)$m[2];
  return ($year * 10) + $quarter;
}

function fetchExternalProjects(bool $forceRefresh = false): array {
  $url = 'https://api.worthysproperties.com/projects';
  $cachePath = __DIR__ . '/../storage/cache/external_projects.json';
  $logPath = __DIR__ . '/../storage/logs/external_projects.log';
  $cachedPayload = null;
  $warning = null;

  if (is_file($cachePath)) {
    $cachedRaw = @file_get_contents($cachePath);
    if ($cachedRaw !== false) {
      $cachedPayload = json_decode($cachedRaw, true);
    }
  }

  if (!$forceRefresh && is_array($cachedPayload)) {
    return normalizeExternalProjectsResponse($cachedPayload, 200, null, null);
  }

  $headers = ['Accept: application/json'];
  $apiKey = getenv('WORTHY_PROJECTS_API_KEY') ?: '';
  if ($apiKey !== '') {
    $headers[] = 'Authorization: Bearer ' . $apiKey;
  }

  $status = 0;
  $error = null;
  $raw = null;
  $ch = curl_init($url);
  if ($ch === false) {
    $error = 'Unable to initialize cURL.';
  } else {
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_TIMEOUT => 60,
      CURLOPT_CONNECTTIMEOUT => 15,
      CURLOPT_ENCODING => '',
    ]);
    $raw = curl_exec($ch);
    if ($raw === false) {
      $error = 'cURL error: ' . curl_error($ch);
    } else {
      $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
      if ($status >= 400) {
        $error = 'HTTP error: ' . $status;
      }
    }
    curl_close($ch);
  }

  if ($error !== null || $raw === false || $raw === null) {
    logExternalProjectsFailure($logPath, $status, $error ?? 'Unknown error');
    if (is_array($cachedPayload)) {
      $warning = 'Showing cached data.';
      return normalizeExternalProjectsResponse($cachedPayload, $status ?: 200, null, $warning);
    }
    return [
      'ok' => false,
      'status' => $status,
      'error' => $error ?? 'Failed to fetch external projects.',
      'meta' => [
        'source' => null,
        'lastUpdated' => null,
        'ageHours' => null,
        'isStale' => null,
        'count' => null,
      ],
      'projects' => [],
    ];
  }

  $payload = json_decode((string)$raw, true);
  if (!is_array($payload)) {
    logExternalProjectsFailure($logPath, $status ?: 200, 'Invalid JSON response');
    if (is_array($cachedPayload)) {
      $warning = 'Showing cached data.';
      return normalizeExternalProjectsResponse($cachedPayload, $status ?: 200, null, $warning);
    }
    return [
      'ok' => false,
      'status' => $status ?: 200,
      'error' => 'Invalid JSON response.',
      'meta' => [
        'source' => null,
        'lastUpdated' => null,
        'ageHours' => null,
        'isStale' => null,
        'count' => null,
      ],
      'projects' => [],
    ];
  }

  $cacheDir = dirname($cachePath);
  if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0775, true);
  }
  @file_put_contents($cachePath, (string)$raw);

  return normalizeExternalProjectsResponse($payload, $status ?: 200, null, null);
}

function normalizeExternalProjectsResponse(array $payload, int $status, ?string $error, ?string $warning): array {
  $projects = [];
  foreach (($payload['data'] ?? []) as $item) {
    if (!is_array($item)) {
      continue;
    }
    $project = $item;
    $project['price_amount'] = parsePriceToInt($project['price'] ?? null);
    $project['handover_key'] = handoverSortKey($project['handover'] ?? null);
    $projects[] = $project;
  }

  $result = [
    'ok' => true,
    'status' => $status,
    'error' => $error,
    'meta' => [
      'source' => $payload['source'] ?? null,
      'lastUpdated' => $payload['lastUpdated'] ?? null,
      'ageHours' => $payload['ageHours'] ?? null,
      'isStale' => $payload['isStale'] ?? null,
      'count' => $payload['count'] ?? null,
    ],
    'projects' => $projects,
  ];
  if ($warning !== null) {
    $result['warning'] = $warning;
  }
  return $result;
}

function logExternalProjectsFailure(string $logPath, int $status, string $message): void {
  $line = sprintf("[%s] status=%s error=%s\n", date('Y-m-d H:i:s'), (string)$status, $message);
  @file_put_contents($logPath, $line, FILE_APPEND);
}

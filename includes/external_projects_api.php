<?php
declare(strict_types=1);

function externalProjectsApiHeaders(): array {
  $headers = ['Accept: application/json'];
  $apiKey = getenv('WORTHY_PROJECTS_API_KEY') ?: '';
  if ($apiKey !== '') {
    $headers[] = 'Authorization: Bearer ' . $apiKey;
  }
  return $headers;
}

function externalProjectsApiBaseUrl(): string {
  $base = getenv('WORTHY_PROJECTS_API_BASE_URL') ?: 'https://api.worthysproperties.com';
  return rtrim($base, '/');
}

function externalProjectsApiRequest(string $url): array {
  $headers = externalProjectsApiHeaders();
  $status = 0;
  $error = null;
  $raw = null;

  $ch = curl_init($url);
  if ($ch === false) {
    return ['status' => 0, 'error' => 'Unable to initialize cURL.', 'raw' => null];
  }

  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 25,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_LOW_SPEED_LIMIT => 1024,
    CURLOPT_LOW_SPEED_TIME => 10,
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

  return ['status' => $status, 'error' => $error, 'raw' => $raw];
}

function externalProjectsRefreshStatus(): array {
  $status = 0;
  $error = null;
  $raw = null;
  $url = externalProjectsApiBaseUrl() . '/refresh/status';

  $ch = curl_init($url);
  if ($ch === false) {
    return ['ok' => false, 'status' => 0, 'error' => 'Unable to initialize cURL.', 'data' => null];
  }

  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
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

  if ($error !== null || $raw === null || $raw === false) {
    return ['ok' => false, 'status' => $status, 'error' => $error ?? 'Failed to fetch refresh status.', 'data' => null];
  }

  $payload = json_decode((string)$raw, true);
  if (!is_array($payload)) {
    return ['ok' => false, 'status' => $status ?: 200, 'error' => 'Invalid JSON response.', 'data' => null];
  }

  return ['ok' => true, 'status' => $status ?: 200, 'error' => null, 'data' => $payload];
}

function externalProjectsReadCache(string $cachePath): array {
  if (!is_file($cachePath)) {
    return ['payload' => null, 'timestamp' => null];
  }
  $cachedRaw = @file_get_contents($cachePath);
  if ($cachedRaw === false) {
    return ['payload' => null, 'timestamp' => null];
  }
  $payload = json_decode($cachedRaw, true);
  if (!is_array($payload)) {
    return ['payload' => null, 'timestamp' => null];
  }
  $timestamp = @filemtime($cachePath) ?: null;
  return ['payload' => $payload, 'timestamp' => $timestamp];
}

function externalProjectsWriteCache(string $cachePath, string $raw): void {
  $cacheDir = dirname($cachePath);
  if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0775, true);
  }
  @file_put_contents($cachePath, $raw);
}

function externalProjectsLogFailure(string $logPath, int $status, string $message): void {
  $line = sprintf("[%s] status=%s error=%s\n", date('Y-m-d H:i:s'), (string)$status, $message);
  @file_put_contents($logPath, $line, FILE_APPEND);
}

function fetchExternalProjectsPage(int $page, int $perPage, bool $forceRefresh = false): array {
  $page = max(1, $page);
  $perPage = max(1, min(100, $perPage));
  $url = externalProjectsApiBaseUrl() . '/projects?page=' . $page . '&per_page=' . $perPage;
  $cachePath = __DIR__ . '/../storage/cache/external_projects_page_' . $page . '_' . $perPage . '.json';
  $logPath = __DIR__ . '/../storage/logs/external_projects_api.log';

  $cached = externalProjectsReadCache($cachePath);
  if (!$forceRefresh && is_array($cached['payload'])) {
    return normalizeExternalProjectsListResponse($cached['payload'], 200, null, null, true, $cached['timestamp'], $page, $perPage);
  }

  $result = externalProjectsApiRequest($url);
  $status = (int)$result['status'];
  $error = $result['error'];
  $raw = $result['raw'];

  if ($error !== null || $raw === null || $raw === false) {
    externalProjectsLogFailure($logPath, $status, $error ?? 'Unknown error');
    if (is_array($cached['payload'])) {
      return normalizeExternalProjectsListResponse($cached['payload'], $status ?: 200, null, 'Showing cached data.', true, $cached['timestamp'], $page, $perPage);
    }
    return [
      'ok' => false,
      'status' => $status,
      'error' => $error ?? 'Failed to fetch external projects.',
      'meta' => [
        'page' => $page,
        'perPage' => $perPage,
        'cache' => [
          'hit' => false,
          'timestamp' => null,
          'ageSeconds' => null,
        ],
      ],
      'data' => [],
    ];
  }

  $payload = json_decode((string)$raw, true);
  if (!is_array($payload)) {
    externalProjectsLogFailure($logPath, $status ?: 200, 'Invalid JSON response');
    if (is_array($cached['payload'])) {
      return normalizeExternalProjectsListResponse($cached['payload'], $status ?: 200, null, 'Showing cached data.', true, $cached['timestamp'], $page, $perPage);
    }
    return [
      'ok' => false,
      'status' => $status ?: 200,
      'error' => 'Invalid JSON response.',
      'meta' => [
        'page' => $page,
        'perPage' => $perPage,
        'cache' => [
          'hit' => false,
          'timestamp' => null,
          'ageSeconds' => null,
        ],
      ],
      'data' => [],
    ];
  }

  externalProjectsWriteCache($cachePath, (string)$raw);

  return normalizeExternalProjectsListResponse($payload, $status ?: 200, null, null, false, time(), $page, $perPage);
}

function normalizeExternalProjectsListResponse(
  array $payload,
  int $status,
  ?string $error,
  ?string $warning,
  bool $cacheHit,
  ?int $cacheTimestamp,
  int $page,
  int $perPage
): array {
  $ageSeconds = null;
  if (is_int($cacheTimestamp)) {
    $ageSeconds = max(0, time() - $cacheTimestamp);
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
      'page' => $page,
      'perPage' => $perPage,
      'cache' => [
        'hit' => $cacheHit,
        'timestamp' => $cacheTimestamp,
        'ageSeconds' => $ageSeconds,
      ],
    ],
    'data' => $payload['data'] ?? [],
  ];

  if ($warning !== null) {
    $result['warning'] = $warning;
  }

  return $result;
}

function fetchExternalProjectDetail(int $projectId, bool $forceRefresh = false): array {
  $projectId = max(1, $projectId);
  $url = externalProjectsApiBaseUrl() . '/projects/' . $projectId;
  $cachePath = __DIR__ . '/../storage/cache/external_project_' . $projectId . '.json';
  $logPath = __DIR__ . '/../storage/logs/external_projects_api.log';

  $cached = externalProjectsReadCache($cachePath);
  if (!$forceRefresh && is_array($cached['payload'])) {
    return normalizeExternalProjectDetailResponse($cached['payload'], 200, null, null, true, $cached['timestamp'], $projectId);
  }

  $result = externalProjectsApiRequest($url);
  $status = (int)$result['status'];
  $error = $result['error'];
  $raw = $result['raw'];

  if ($error !== null || $raw === null || $raw === false) {
    externalProjectsLogFailure($logPath, $status, $error ?? 'Unknown error');
    if (is_array($cached['payload'])) {
      return normalizeExternalProjectDetailResponse($cached['payload'], $status ?: 200, null, 'Showing cached data.', true, $cached['timestamp'], $projectId);
    }
    return [
      'ok' => false,
      'status' => $status,
      'error' => $error ?? 'Failed to fetch project details.',
      'meta' => [
        'projectId' => $projectId,
        'cache' => [
          'hit' => false,
          'timestamp' => null,
          'ageSeconds' => null,
        ],
      ],
      'data' => null,
    ];
  }

  $payload = json_decode((string)$raw, true);
  if (!is_array($payload)) {
    externalProjectsLogFailure($logPath, $status ?: 200, 'Invalid JSON response');
    if (is_array($cached['payload'])) {
      return normalizeExternalProjectDetailResponse($cached['payload'], $status ?: 200, null, 'Showing cached data.', true, $cached['timestamp'], $projectId);
    }
    return [
      'ok' => false,
      'status' => $status ?: 200,
      'error' => 'Invalid JSON response.',
      'meta' => [
        'projectId' => $projectId,
        'cache' => [
          'hit' => false,
          'timestamp' => null,
          'ageSeconds' => null,
        ],
      ],
      'data' => null,
    ];
  }

  externalProjectsWriteCache($cachePath, (string)$raw);

  return normalizeExternalProjectDetailResponse($payload, $status ?: 200, null, null, false, time(), $projectId);
}

function normalizeExternalProjectDetailResponse(
  array $payload,
  int $status,
  ?string $error,
  ?string $warning,
  bool $cacheHit,
  ?int $cacheTimestamp,
  int $projectId
): array {
  $ageSeconds = null;
  if (is_int($cacheTimestamp)) {
    $ageSeconds = max(0, time() - $cacheTimestamp);
  }

  $result = [
    'ok' => true,
    'status' => $status,
    'error' => $error,
    'meta' => [
      'projectId' => $projectId,
      'cache' => [
        'hit' => $cacheHit,
        'timestamp' => $cacheTimestamp,
        'ageSeconds' => $ageSeconds,
      ],
    ],
    'data' => $payload['data'] ?? $payload,
  ];

  if ($warning !== null) {
    $result['warning'] = $warning;
  }

  return $result;
}

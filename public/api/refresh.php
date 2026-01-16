<?php
declare(strict_types=1);

require_once __DIR__ . '/../../init.php';

use App\Helpers\Logger;

require_role(['ADMIN', 'CEO']);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  header('Content-Type: application/json');
  echo json_encode(['ok' => false, 'status' => 405, 'error' => 'Method not allowed.']);
  exit;
}

verify_csrf();

$lockPath = __DIR__ . '/../../storage/cache/external_projects_refresh_api_lock.json';
$cooldownSeconds = 120;
$lockRaw = @file_get_contents($lockPath);
$lockData = is_string($lockRaw) ? json_decode($lockRaw, true) : null;
$lastAttempt = is_array($lockData) ? (int)($lockData['last_attempt'] ?? 0) : 0;
if ($lastAttempt > 0 && (time() - $lastAttempt) < $cooldownSeconds) {
  $retryAfter = $cooldownSeconds - (time() - $lastAttempt);
  http_response_code(429);
  header('Content-Type: application/json');
  header('Retry-After: ' . max(1, $retryAfter));
  echo json_encode([
    'ok' => false,
    'status' => 429,
    'error' => 'Refresh was run recently. Please wait before trying again.',
    'retry_after_seconds' => max(1, $retryAfter),
  ]);
  exit;
}

$lockDir = dirname($lockPath);
if (!is_dir($lockDir)) {
  @mkdir($lockDir, 0775, true);
}
@file_put_contents($lockPath, json_encode([
  'last_attempt' => time(),
], JSON_UNESCAPED_SLASHES));

$apiKey = getenv('WORTHY_PROJECTS_API_KEY') ?: '';
if ($apiKey === '') {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode(['ok' => false, 'status' => 500, 'error' => 'Missing API key.']);
  exit;
}

$baseUrl = getenv('WORTHY_PROJECTS_API_BASE_URL') ?: 'https://api.worthysproperties.com';
$baseUrl = rtrim($baseUrl, '/');

$full = ($_POST['full'] ?? '') === '1';
$url = $baseUrl . '/refresh' . ($full ? '?full=true' : '');

$ch = curl_init($url);
if ($ch === false) {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode(['ok' => false, 'status' => 500, 'error' => 'Unable to initialize cURL.']);
  exit;
}

curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    'Accept: application/json',
    'Authorization: Bearer ' . $apiKey,
  ],
  CURLOPT_TIMEOUT => 90,
  CURLOPT_CONNECTTIMEOUT => 10,
  CURLOPT_ENCODING => '',
]);

$raw = curl_exec($ch);
if ($raw === false) {
  $error = 'cURL error: ' . curl_error($ch);
  curl_close($ch);
  Logger::error('External refresh API failed', ['error' => $error, 'full' => $full]);
  http_response_code(502);
  header('Content-Type: application/json');
  echo json_encode(['ok' => false, 'status' => 502, 'error' => $error]);
  exit;
}

$status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

$decoded = json_decode((string)$raw, true);
if (!is_array($decoded)) {
  Logger::error('External refresh API returned invalid JSON', ['status' => $status, 'full' => $full]);
  http_response_code(502);
  header('Content-Type: application/json');
  echo json_encode(['ok' => false, 'status' => 502, 'error' => 'Invalid JSON response.']);
  exit;
}

http_response_code($status ?: 200);
header('Content-Type: application/json');
echo json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

<?php
declare(strict_types=1);

require_once __DIR__ . '/../init.php';

require_role(['ADMIN', 'CEO', 'AGENT']);

$token = (string)($_GET['id'] ?? '');
if ($token === '' || strpos($token, '.') === false) {
  http_response_code(400);
  echo 'Missing image id.';
  exit;
}

[$payload, $sig] = explode('.', $token, 2);
$expected = hash_hmac('sha256', $payload, app_key());
if (!hash_equals($expected, $sig)) {
  http_response_code(403);
  echo 'Invalid image token.';
  exit;
}

$payload .= str_repeat('=', (4 - (strlen($payload) % 4)) % 4);
$url = base64_decode(strtr($payload, '-_', '+/'), true);
if ($url === false || $url === '') {
  http_response_code(400);
  echo 'Invalid image token.';
  exit;
}

$parts = parse_url($url);
if (!$parts || ($parts['scheme'] ?? '') !== 'https') {
  http_response_code(400);
  echo 'Invalid image URL.';
  exit;
}

$cacheDir = __DIR__ . '/../storage/cache/external_projects_images';
if (!is_dir($cacheDir)) {
  @mkdir($cacheDir, 0775, true);
}
$cachedFile = $cacheDir . '/' . preg_replace('/[^a-z0-9._-]/i', '', $sig);
$logPath = __DIR__ . '/../storage/logs/external_projects_images.log';

if (is_file($cachedFile)) {
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = $finfo ? finfo_file($finfo, $cachedFile) : null;
  if ($finfo) finfo_close($finfo);
  if (!$mime) $mime = 'application/octet-stream';
  header('Content-Type: ' . $mime);
  header('Cache-Control: public, max-age=86400, immutable');
  readfile($cachedFile);
  exit;
}

if (!function_exists('curl_init')) {
  $context = stream_context_create([
    'http' => [
      'method' => 'GET',
      'timeout' => 30,
      'header' => "Accept: image/*\r\n",
    ],
    'ssl' => [
      'verify_peer' => true,
      'verify_peer_name' => true,
    ],
  ]);
  $body = @file_get_contents($url, false, $context);
  if ($body === false) {
    @file_put_contents($logPath, '[' . date('Y-m-d H:i:s') . '] stream error url=' . $url . "\n", FILE_APPEND);
    http_response_code(404);
    echo 'Image not found.';
    exit;
  }
  @file_put_contents($cachedFile, $body);
  header('Content-Type: application/octet-stream');
  header('Cache-Control: public, max-age=86400, immutable');
  echo $body;
  exit;
}

$ch = curl_init($url);
if ($ch === false) {
  http_response_code(500);
  echo 'Unable to fetch image.';
  exit;
}
$cacheHandle = @fopen($cachedFile, 'wb');
$contentType = '';
$headersSent = false;
$bytesWritten = 0;

curl_setopt_array($ch, [
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_CONNECTTIMEOUT => 10,
  CURLOPT_HTTPHEADER => ['Accept: image/*'],
  CURLOPT_USERAGENT => 'WorthyCRM/1.0 (+image-proxy)',
  CURLOPT_SSL_VERIFYPEER => true,
  CURLOPT_SSL_VERIFYHOST => 2,
  CURLOPT_HEADERFUNCTION => static function ($ch, string $header) use (&$contentType): int {
    $len = strlen($header);
    if (stripos($header, 'Content-Type:') === 0) {
      $contentType = trim(substr($header, strlen('Content-Type:')));
    }
    return $len;
  },
  CURLOPT_WRITEFUNCTION => static function ($ch, string $data) use (&$headersSent, &$contentType, $cacheHandle, &$bytesWritten): int {
    if (!$headersSent) {
      if ($contentType === '') $contentType = 'application/octet-stream';
      header('Content-Type: ' . $contentType);
      header('Cache-Control: public, max-age=3600');
      $headersSent = true;
    }
    if ($cacheHandle) {
      $written = fwrite($cacheHandle, $data);
      if ($written !== false) {
        $bytesWritten += $written;
      }
    }
    echo $data;
    return strlen($data);
  },
]);

$ok = curl_exec($ch);
$status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($cacheHandle) {
  fclose($cacheHandle);
}

if ($ok === false || $status >= 400) {
  if (is_file($cachedFile)) {
    @unlink($cachedFile);
  }
  @file_put_contents($logPath, '[' . date('Y-m-d H:i:s') . '] curl error status=' . $status . ' err=' . $curlErr . ' url=' . $url . "\n", FILE_APPEND);
  if (!$headersSent) {
    http_response_code(404);
    echo 'Image not found.';
  }
  exit;
}

if ($bytesWritten <= 0 && is_file($cachedFile)) {
  @unlink($cachedFile);
}

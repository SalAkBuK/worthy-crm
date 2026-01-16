<?php
declare(strict_types=1);

require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/../../includes/external_projects_api.php';

$page = (int)($_GET['page'] ?? 1);
$perPage = (int)($_GET['per_page'] ?? 20);
$refresh = (string)($_GET['refresh'] ?? '');
$logList = (string)($_GET['log'] ?? '');

$response = fetchExternalProjectsPage($page, $perPage, $refresh === '1');

if ($logList === '1') {
  $logDir = __DIR__ . '/../../storage/logs';
  if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
  }
  $logPath = $logDir . '/external_projects_list_p' . max(1, $page) . '_pp' . max(1, $perPage) . '_' . date('Ymd_His') . '.json';
  $snapshot = [
    'page' => $page,
    'per_page' => $perPage,
    'fetched_at' => date('c'),
    'status' => $response['status'] ?? null,
    'warning' => $response['warning'] ?? null,
    'meta' => $response['meta'] ?? null,
    'data' => $response['data'] ?? null,
  ];
  @file_put_contents($logPath, json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}

http_response_code((int)($response['status'] ?? 200));
header('Content-Type: application/json');

echo json_encode($response, JSON_UNESCAPED_UNICODE);

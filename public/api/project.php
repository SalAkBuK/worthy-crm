<?php
declare(strict_types=1);

require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/../../includes/external_projects_api.php';

$projectId = (int)($_GET['id'] ?? 0);
if ($projectId < 1) {
  http_response_code(400);
  header('Content-Type: application/json');
  echo json_encode(['ok' => false, 'status' => 400, 'error' => 'Missing or invalid id.']);
  exit;
}

$refresh = (string)($_GET['refresh'] ?? '');
$response = fetchExternalProjectDetail($projectId, $refresh === '1');

http_response_code((int)($response['status'] ?? 200));
header('Content-Type: application/json');

echo json_encode($response, JSON_UNESCAPED_UNICODE);

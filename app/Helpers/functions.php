<?php
declare(strict_types=1);

function e(?string $value): string {
  return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string {
  $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
  if ($base === '') $base = '';
  return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): void {
  header('Location: ' . url($path));
  exit;
}

function flash(string $type, string $message): void {
  $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array {
  $items = $_SESSION['_flash'] ?? [];
  unset($_SESSION['_flash']);
  return $items;
}

function toast(string $type, string $message): void {
  $_SESSION['_toast'][] = ['type' => $type, 'message' => $message];
}

function get_toasts(): array {
  $items = $_SESSION['_toast'] ?? [];
  unset($_SESSION['_toast']);
  return $items;
}

function csrf_token(): string {
  if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['_csrf'];
}

function csrf_field(): string {
  return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void {
  $token = $_POST['_csrf'] ?? '';
  if (!$token || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
    http_response_code(419);
    require __DIR__ . '/../Views/errors/419.php';
    exit;
  }
}

function app_env(): string {
  return getenv('APP_ENV') ?: 'production';
}

function app_debug(): bool {
  return (getenv('APP_DEBUG') ?: '0') === '1';
}

function app_key(): string {
  return getenv('APP_KEY') ?: 'change_me';
}

function current_user(): ?array {
  return $_SESSION['user'] ?? null;
}

function require_login(): void {
  if (!current_user()) {
    flash('warning', 'Please login to continue.');
    redirect('login');
  }
}

function require_role(array $roles): void {
  require_login();
  $role = current_user()['role'] ?? '';
  if (!in_array($role, $roles, true)) {
    http_response_code(403);
    require __DIR__ . '/../Views/errors/403.php';
    exit;
  }
}

function parse_date(?string $date): ?string {
  if (!$date) return null;
  $date = trim($date);
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return null;
  return $date;
}

function parse_aed_amount(?string $raw): ?int {
  if ($raw === null) return null;
  $raw = trim((string)$raw);
  if ($raw === '') return null;
  $raw = str_ireplace('aed', '', $raw);
  if (preg_match('/\d[\d, ]*/', $raw, $m)) {
    $value = str_replace([',', ' '], '', $m[0]);
    if ($value === '' || !ctype_digit($value)) return null;
    return (int)$value;
  }
  return null;
}

function parse_ini_bytes(?string $value): int {
  if ($value === null) return 0;
  $value = trim($value);
  if ($value === '') return 0;
  $unit = strtolower(substr($value, -1));
  if (ctype_digit($value)) {
    return (int)$value;
  }
  $num = (float)substr($value, 0, -1);
  switch ($unit) {
    case 'g':
      return (int)round($num * 1024 * 1024 * 1024);
    case 'm':
      return (int)round($num * 1024 * 1024);
    case 'k':
      return (int)round($num * 1024);
    default:
      return (int)round($num);
  }
}

function format_bytes(int $bytes): string {
  if ($bytes <= 0) return '0B';
  $units = ['B','KB','MB','GB','TB'];
  $idx = 0;
  $value = (float)$bytes;
  while ($value >= 1024 && $idx < count($units) - 1) {
    $value /= 1024;
    $idx++;
  }
  $precision = $value >= 100 ? 0 : ($value >= 10 ? 1 : 2);
  return rtrim(rtrim(number_format($value, $precision, '.', ''), '0'), '.') . $units[$idx];
}

function upload_limit_message(int $appLimitBytes): string {
  $uploadMax = ini_get('upload_max_filesize');
  $postMax = ini_get('post_max_size');
  $parts = [];
  if ($uploadMax !== false && $uploadMax !== '') $parts[] = 'server upload limit: ' . $uploadMax;
  if ($postMax !== false && $postMax !== '') $parts[] = 'post limit: ' . $postMax;
  $suffix = $parts ? (' (' . implode(', ', $parts) . ').') : '.';
  return 'Max allowed: ' . format_bytes($appLimitBytes) . $suffix;
}

function paginate_meta(int $total, int $page, int $perPage): array {
  $pages = (int)ceil(max(1, $total) / $perPage);
  $page = max(1, min($pages, $page));
  $offset = ($page - 1) * $perPage;
  return compact('total', 'page', 'perPage', 'pages', 'offset');
}

function build_query(array $overrides = []): string {
  $params = array_merge($_GET, $overrides);
  foreach ($params as $k => $v) {
    if ($v === null || $v === '') unset($params[$k]);
  }
  return http_build_query($params);
}

function notification_types_for_role(string $role): ?array {
  if ($role === 'CEO') {
    return ['lead_reopen', 'lead_deleted', 'listing_deleted', 'agent_deleted', 'agent_status_change', 'weekly_summary'];
  }
  return null;
}

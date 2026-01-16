<?php
declare(strict_types=1);

require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../includes/external_projects_api.php';
require_once __DIR__ . '/../includes/external_projects_normalize.php';

require_role(['ADMIN', 'CEO', 'AGENT']);

function query_string(string $key): string {
  return trim((string)($_GET[$key] ?? ''));
}

function query_int(string $key): ?int {
  $value = trim((string)($_GET[$key] ?? ''));
  if ($value === '' || !is_numeric($value)) return null;
  return (int)$value;
}

function query_bool(string $key): bool {
  return ($_GET[$key] ?? '') === '1';
}

function normalize_title(string $value): string {
  $value = mb_strtolower($value);
  $value = preg_replace('/[^a-z0-9\s]+/i', ' ', $value) ?? '';
  $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';
  return $value;
}

function title_fuzzy_score(string $query, string $title): float {
  $query = normalize_title($query);
  $title = normalize_title($title);
  if ($query === '') return 1.0;
  if ($title === '') return 0.0;
  if (strpos($title, $query) !== false) return 1.0;

  $queryTokens = array_filter(preg_split('/\s+/', $query) ?: [], function ($token) {
    return strlen($token) >= 2;
  });
  $tokenMatches = 0;
  foreach ($queryTokens as $token) {
    if (strpos($title, $token) !== false) {
      $tokenMatches++;
    }
  }
  $tokenScore = $queryTokens ? ($tokenMatches / count($queryTokens)) : 0.0;

  $maxLen = max(strlen($query), strlen($title));
  $distance = levenshtein($query, $title);
  $similarity = $maxLen > 0 ? (1 - min(1, $distance / $maxLen)) : 0.0;

  return max($tokenScore * 0.9, $similarity);
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 25);
$perPageOptions = [25, 50, 100];
if (!in_array($perPage, $perPageOptions, true)) {
  $perPage = 25;
}

$refresh = (string)($_GET['refresh'] ?? '');
$cachePath = __DIR__ . '/../storage/cache/external_projects_normalized.json';
$cacheMaxAgeSeconds = 6 * 60 * 60;

$error = null;
$normalizedItems = [];
$cacheHit = false;
$cacheTimestamp = null;

if ($refresh !== '1' && is_file($cachePath)) {
  $cacheTimestamp = @filemtime($cachePath) ?: null;
  if ($cacheTimestamp !== null && (time() - $cacheTimestamp) < $cacheMaxAgeSeconds) {
    $raw = @file_get_contents($cachePath);
    $payload = is_string($raw) ? json_decode($raw, true) : null;
    if (is_array($payload) && is_array($payload['items'] ?? null)) {
      $normalizedItems = $payload['items'];
      $cacheHit = true;
    }
  }
}

if (!$normalizedItems) {
  set_time_limit(0);
  $perPageFetch = 100;
  $firstResponse = fetchExternalProjectsPage(1, $perPageFetch, $refresh === '1');
  if (!$firstResponse['ok']) {
    $error = (string)($firstResponse['error'] ?? 'Unable to load projects.');
  } else {
    $totalCount = (int)($firstResponse['meta']['count'] ?? 0);
    $pagesToFetch = max(1, (int)ceil($totalCount / $perPageFetch));
    for ($p = 1; $p <= $pagesToFetch; $p++) {
      if ($p > 1) {
        usleep(150000);
      }
      $response = $p === 1 ? $firstResponse : fetchExternalProjectsPage($p, $perPageFetch, $refresh === '1');
      if (empty($response['data']) || !is_array($response['data'])) continue;
      foreach ($response['data'] as $item) {
        if (!is_array($item)) continue;
        $normalizedItems[] = normalize_external_project($item);
      }
    }

    $cacheDir = dirname($cachePath);
    if (!is_dir($cacheDir)) {
      @mkdir($cacheDir, 0775, true);
    }
    @file_put_contents($cachePath, json_encode([
      'generated_at' => date('c'),
      'items' => $normalizedItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
  }
}

$city = query_string('city');
$district = query_string('district');
$type = query_string('type');
$status = query_string('status');
$completed = query_string('completed');
$titleQuery = query_string('q');
$handoverYear = query_int('handover_year');
$priceMin = query_int('price_min');
$priceMax = query_int('price_max');
$bedsListRaw = query_string('beds');
$bedsMin = query_int('beds_min');
$bedsMax = query_int('beds_max');
$sizeMin = query_int('size_min');
$sizeMax = query_int('size_max');
$hasPaymentPlan = query_string('has_payment_plan');
$hasGallery = query_string('has_gallery');
$hasFacilities = query_string('has_facilities');
$sort = query_string('sort');
if ($sort === '') $sort = 'newest';

$bedsList = [];
if ($bedsListRaw !== '') {
  foreach (preg_split('/\s*,\s*/', $bedsListRaw) as $bedVal) {
    if ($bedVal === '') continue;
    if (is_numeric($bedVal)) $bedsList[] = (int)$bedVal;
  }
  $bedsList = array_values(array_unique($bedsList));
}

$priceFilterApplied = ($priceMin !== null || $priceMax !== null);
$bedsFilterApplied = ($bedsList || $bedsMin !== null || $bedsMax !== null);
$sizeFilterApplied = ($sizeMin !== null || $sizeMax !== null);

$filtered = [];

foreach ($normalizedItems as $item) {
  if (!is_array($item)) continue;
  $include = true;

  if ($titleQuery !== '') {
    $titleScore = title_fuzzy_score($titleQuery, (string)($item['title'] ?? ''));
    if ($titleScore < 0.45) {
      $include = false;
    } else {
      $item['_title_score'] = $titleScore;
    }
  }

  if ($city !== '' && strcasecmp((string)$item['city'], $city) !== 0) $include = false;
  if ($include && $district !== '' && strcasecmp((string)$item['district'], $district) !== 0) $include = false;
  if ($include && $type !== '' && strcasecmp((string)$item['type'], $type) !== 0) $include = false;
  if ($include && $status !== '' && strcasecmp((string)$item['status'], $status) !== 0) $include = false;
  if ($include && $completed !== '') {
    $wantCompleted = $completed === '1';
    if ((bool)($item['is_completed'] ?? false) !== $wantCompleted) $include = false;
  }
  if ($include && $handoverYear !== null && (int)$item['completion_year'] !== $handoverYear) $include = false;

  if ($include && $hasPaymentPlan !== '') {
    $want = $hasPaymentPlan === '1';
    if ((bool)$item['has_payment_plan'] !== $want) $include = false;
  }
  if ($include && $hasGallery !== '') {
    $want = $hasGallery === '1';
    if ((bool)$item['has_gallery'] !== $want) $include = false;
  }
  if ($include && $hasFacilities !== '') {
    $want = $hasFacilities === '1';
    if ((bool)$item['has_facilities'] !== $want) $include = false;
  }

  if ($include && $priceFilterApplied) {
    $priceValue = $item['price_start_aed'] ?? null;
    if (is_numeric($priceValue)) {
      $priceValue = (int)$priceValue;
      if ($priceMin !== null && $priceValue < $priceMin) $include = false;
      if ($priceMax !== null && $priceValue > $priceMax) $include = false;
    }
  }

  if ($include && $bedsFilterApplied) {
    $itemMin = $item['beds_min'] ?? null;
    $itemMax = $item['beds_max'] ?? null;
    if ($itemMin !== null && $itemMax !== null) {
      if ($bedsList) {
        $match = false;
        foreach ($bedsList as $bed) {
          if ($bed >= $itemMin && $bed <= $itemMax) {
            $match = true;
            break;
          }
        }
        if (!$match) $include = false;
      } else {
        if ($bedsMin !== null && $itemMax < $bedsMin) $include = false;
        if ($bedsMax !== null && $itemMin > $bedsMax) $include = false;
      }
    }
  }

  if ($include && $sizeFilterApplied) {
    $itemMin = $item['size_min_sqft'] ?? null;
    $itemMax = $item['size_max_sqft'] ?? null;
    if ($itemMin !== null && $itemMax !== null) {
      if ($sizeMin !== null && $itemMax < $sizeMin) $include = false;
      if ($sizeMax !== null && $itemMin > $sizeMax) $include = false;
    }
  }

  if ($include) $filtered[] = $item;
}

usort($filtered, function (array $a, array $b) use ($sort, $titleQuery): int {
  if ($titleQuery !== '') {
    $aScore = (float)($a['_title_score'] ?? 0);
    $bScore = (float)($b['_title_score'] ?? 0);
    if ($aScore !== $bScore) {
      return $bScore <=> $aScore;
    }
  }
  $dir = 1;
  $aVal = null;
  $bVal = null;
  if ($sort === 'price_desc' || $sort === 'price_asc') {
    $aVal = $a['price_start_aed'] ?? null;
    $bVal = $b['price_start_aed'] ?? null;
    $dir = $sort === 'price_desc' ? -1 : 1;
  } elseif ($sort === 'completion_asc') {
    $aVal = $a['completion_date'] ?? null;
    $bVal = $b['completion_date'] ?? null;
    $dir = 1;
  } elseif ($sort === 'images_desc') {
    $aVal = $a['images_count'] ?? null;
    $bVal = $b['images_count'] ?? null;
    $dir = -1;
  } elseif ($sort === 'facilities_desc') {
    $aVal = $a['facilities_count'] ?? null;
    $bVal = $b['facilities_count'] ?? null;
    $dir = -1;
  } else {
    $aVal = $a['created_at'] ?? null;
    $bVal = $b['created_at'] ?? null;
    $dir = -1;
  }

  if ($aVal === null && $bVal === null) return 0;
  if ($aVal === null) return 1;
  if ($bVal === null) return -1;

  if ($sort === 'completion_asc' || $sort === 'newest') {
    $aTs = strtotime((string)$aVal) ?: 0;
    $bTs = strtotime((string)$bVal) ?: 0;
    return ($aTs <=> $bTs) * $dir;
  }

  return ($aVal <=> $bVal) * $dir;
});

$total = count($filtered);
$totalAll = count($normalizedItems);
$pages = max(1, (int)ceil(max(1, $total) / $perPage));
if ($page > $pages) $page = $pages;
$offset = ($page - 1) * $perPage;
$paged = array_slice($filtered, $offset, $perPage);

$cityOptions = [];
$districtOptions = [];
$typeOptions = [];
$statusOptions = [];
$yearOptions = [];
$titleSuggestions = [];
foreach ($normalizedItems as $item) {
  if (!is_array($item)) continue;
  $cityVal = trim((string)($item['city'] ?? ''));
  $districtVal = trim((string)($item['district'] ?? ''));
  $typeVal = trim((string)($item['type'] ?? ''));
  $statusVal = trim((string)($item['status'] ?? ''));
  $titleVal = trim((string)($item['title'] ?? ''));
  $yearVal = $item['completion_year'] ?? null;
  if ($cityVal !== '') $cityOptions[$cityVal] = true;
  if ($districtVal !== '') $districtOptions[$districtVal] = true;
  if ($typeVal !== '') $typeOptions[$typeVal] = true;
  if ($statusVal !== '') $statusOptions[$statusVal] = true;
  if ($titleVal !== '') $titleSuggestions[$titleVal] = true;
  if (is_int($yearVal)) $yearOptions[$yearVal] = true;
}
$cityOptions = array_keys($cityOptions);
$districtOptions = array_keys($districtOptions);
$typeOptions = array_keys($typeOptions);
$statusOptions = array_keys($statusOptions);
$yearOptions = array_keys($yearOptions);
$titleSuggestions = array_keys($titleSuggestions);

sort($cityOptions, SORT_NATURAL | SORT_FLAG_CASE);
sort($districtOptions, SORT_NATURAL | SORT_FLAG_CASE);
sort($typeOptions, SORT_NATURAL | SORT_FLAG_CASE);
sort($statusOptions, SORT_NATURAL | SORT_FLAG_CASE);
sort($titleSuggestions, SORT_NATURAL | SORT_FLAG_CASE);
rsort($yearOptions, SORT_NUMERIC);

$title = 'External Projects';
$page_title = 'External Projects';
$page_subtitle = 'External Projects';
$refreshQuery = build_query(['refresh' => 1]);
$refreshUrl = url('external_projects.php') . ($refreshQuery !== '' ? ('?' . $refreshQuery) : '');
$refreshStatus = externalProjectsRefreshStatus();
$refreshStatusData = $refreshStatus['ok'] ? ($refreshStatus['data'] ?? null) : null;
$user = current_user();
$role = $user['role'] ?? '';
$canRefreshApi = in_array($role, ['ADMIN', 'CEO'], true);
$refreshApiUrl = url('api/refresh.php');
$csrfToken = csrf_token();
$prevUrl = $page > 1 ? url('external_projects.php') . '?' . build_query(['page' => $page - 1]) : '';
$nextUrl = $page < $pages ? url('external_projects.php') . '?' . build_query(['page' => $page + 1]) : '';
$pageWindow = 2;
$startPage = max(1, $page - $pageWindow);
$endPage = min($pages, $page + $pageWindow);
if (($endPage - $startPage) < ($pageWindow * 2)) {
  $startPage = max(1, $endPage - ($pageWindow * 2));
}

ob_start();
?>
<div class="row">
  <div class="col-12">
    <h4 class="mb-2">External Projects</h4>
    <div class="text-muted mb-3">
      Total projects: <?= e((string)number_format($totalAll)) ?>
      <?php if ($total !== $totalAll): ?>| Filtered: <?= e((string)number_format($total)) ?><?php endif; ?>
    </div>

    <?php if ($error !== null): ?>
      <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>


    <form method="get" class="row g-2 align-items-end mb-3">
      <div class="col-md-3">
        <label class="form-label">City</label>
        <select name="city" class="form-select">
          <option value="">All</option>
          <?php foreach ($cityOptions as $opt): ?>
            <option value="<?= e($opt) ?>" <?= $opt === $city ? 'selected' : '' ?>><?= e($opt) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">District</label>
        <select name="district" class="form-select">
          <option value="">All</option>
          <?php foreach ($districtOptions as $opt): ?>
            <option value="<?= e($opt) ?>" <?= $opt === $district ? 'selected' : '' ?>><?= e($opt) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Property type</label>
        <select name="type" class="form-select">
          <option value="">All</option>
          <?php foreach ($typeOptions as $opt): ?>
            <option value="<?= e($opt) ?>" <?= $opt === $type ? 'selected' : '' ?>><?= e($opt) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="">All</option>
          <?php foreach ($statusOptions as $opt): ?>
            <option value="<?= e($opt) ?>" <?= $opt === $status ? 'selected' : '' ?>><?= e($opt) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Completed</label>
        <select name="completed" class="form-select">
          <option value="">All</option>
          <option value="1" <?= $completed === '1' ? 'selected' : '' ?>>Completed</option>
          <option value="0" <?= $completed === '0' ? 'selected' : '' ?>>Not completed</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">Handover year</label>
        <select name="handover_year" class="form-select">
          <option value="">All</option>
          <?php foreach ($yearOptions as $opt): ?>
            <option value="<?= e((string)$opt) ?>" <?= $handoverYear === $opt ? 'selected' : '' ?>><?= e((string)$opt) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Price min (AED)</label>
        <input type="number" name="price_min" class="form-control" value="<?= e((string)($priceMin ?? '')) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Price max (AED)</label>
        <input type="number" name="price_max" class="form-control" value="<?= e((string)($priceMax ?? '')) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Beds (list)</label>
        <input type="text" name="beds" class="form-control" placeholder="0,1,2" value="<?= e($bedsListRaw) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Beds min</label>
        <input type="number" name="beds_min" class="form-control" value="<?= e((string)($bedsMin ?? '')) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Beds max</label>
        <input type="number" name="beds_max" class="form-control" value="<?= e((string)($bedsMax ?? '')) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Size min (sqft)</label>
        <input type="number" name="size_min" class="form-control" value="<?= e((string)($sizeMin ?? '')) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Size max (sqft)</label>
        <input type="number" name="size_max" class="form-control" value="<?= e((string)($sizeMax ?? '')) ?>">
      </div>

      <div class="col-md-2">
        <label class="form-label">Has payment plan</label>
        <select name="has_payment_plan" class="form-select">
          <option value="">Any</option>
          <option value="1" <?= $hasPaymentPlan === '1' ? 'selected' : '' ?>>Yes</option>
          <option value="0" <?= $hasPaymentPlan === '0' ? 'selected' : '' ?>>No</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Has gallery</label>
        <select name="has_gallery" class="form-select">
          <option value="">Any</option>
          <option value="1" <?= $hasGallery === '1' ? 'selected' : '' ?>>Yes</option>
          <option value="0" <?= $hasGallery === '0' ? 'selected' : '' ?>>No</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Has facilities</label>
        <select name="has_facilities" class="form-select">
          <option value="">Any</option>
          <option value="1" <?= $hasFacilities === '1' ? 'selected' : '' ?>>Yes</option>
          <option value="0" <?= $hasFacilities === '0' ? 'selected' : '' ?>>No</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Sort</label>
        <select name="sort" class="form-select">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
          <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price (Low)</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price (High)</option>
          <option value="completion_asc" <?= $sort === 'completion_asc' ? 'selected' : '' ?>>Completion Date</option>
          <option value="images_desc" <?= $sort === 'images_desc' ? 'selected' : '' ?>>Most Images</option>
          <option value="facilities_desc" <?= $sort === 'facilities_desc' ? 'selected' : '' ?>>Most Facilities</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Per page</label>
        <select name="per_page" class="form-select">
          <?php foreach ($perPageOptions as $opt): ?>
            <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6 position-relative">
        <label class="form-label">Title search</label>
        <input type="text" name="q" id="titleSearchInput" class="form-control" placeholder="e.g., Amra Residences" value="<?= e($titleQuery) ?>" autocomplete="off">
        <div id="titleSearchResults" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 50; max-height: 220px; overflow-y: auto;"></div>
      </div>

      <div class="col-md-12">
        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
        <a href="<?= e(url('external_projects.php')) ?>" class="btn btn-light btn-sm">Reset</a>
        <?php if ($canRefreshApi): ?>
          <button type="button" class="btn btn-outline-info btn-sm" id="refreshApiBtn">
            <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
            <span class="refresh-api-label">Refresh Data</span>
          </button>
          <input type="hidden" id="refreshApiToken" value="<?= e($csrfToken) ?>">
        <?php endif; ?>
      </div>
    </form>

    <?php if ($canRefreshApi): ?>
      <div id="refreshApiAlert" class="mt-3"></div>
    <?php endif; ?>

    <?php if (!$paged && $error === null): ?>
      <div class="alert alert-info">No projects found.</div>
    <?php endif; ?>

    <?php if ($paged): ?>
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="text-muted">
          Showing <?= $total > 0 ? ($offset + 1) : 0 ?>-<?= min($offset + $perPage, $total) ?> of <?= $total ?>
        </div>
        <?php if ($pages > 1): ?>
          <nav aria-label="Page navigation example">
            <ul class="pagination mb-0">
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e($prevUrl) ?>" aria-label="Previous">
                  <span aria-hidden="true">&laquo;</span>
                </a>
              </li>
              <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                <?php $pageUrl = url('external_projects.php') . '?' . build_query(['page' => $p]); ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                  <a class="page-link" href="<?= e($pageUrl) ?>"><?= $p ?></a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e($nextUrl) ?>" aria-label="Next">
                  <span aria-hidden="true">&raquo;</span>
                </a>
              </li>
            </ul>
          </nav>
        <?php endif; ?>
      </div>

      <div class="row g-2">
        <?php foreach ($paged as $item): ?>
          <?php
          $projectId = (int)($item['id'] ?? 0);
          $itemTitle = (string)($item['title'] ?? '');
          $itemDistrict = (string)($item['district'] ?? '');
          $itemStatus = (string)($item['status'] ?? '');
          $itemImage = (string)($item['image'] ?? '');
          $itemHandover = (string)($item['completion_date'] ?? '');
          $isCompleted = !empty($item['is_completed']);
          $itemPrice = $item['price_is_tba'] ? 'TBA' : ('AED ' . number_format((int)$item['price_start_aed']));
          $detailUrl = url('external_project_show.php') . '?' . build_query([
            'id' => $projectId ?: null,
            'title' => $itemTitle,
            'district' => $itemDistrict,
            'price' => $itemPrice,
            'handover' => $itemHandover,
            'image' => $itemImage,
          ]);
          ?>
          <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="card overflow-hidden h-100">
              <div class="position-relative">
                <?php if ($itemImage !== ''): ?>
                  <?php $payload = rtrim(strtr(base64_encode($itemImage), '+/', '-_'), '='); ?>
                  <?php $signature = hash_hmac('sha256', $payload, app_key()); ?>
                  <?php $imageToken = $payload . '.' . $signature; ?>
                  <img src="<?= e(url('image-proxy.php?id=' . $imageToken)) ?>"
                       data-fallback="<?= e($itemImage) ?>"
                       onerror="if (this.dataset.fallback) { this.src = this.dataset.fallback; this.removeAttribute('data-fallback'); }"
                       alt="<?= e($itemTitle) ?>"
                       loading="lazy"
                       class="img-fluid rounded-top"
                       style="height: 160px; width: 100%; object-fit: cover;">
                <?php else: ?>
                  <div class="bg-light d-flex align-items-center justify-content-center" style="height: 160px;">
                    <span class="text-muted">No image</span>
                  </div>
                <?php endif; ?>
                <span class="position-absolute top-0 start-0 p-1">
                  <span class="badge fs-12" style="background: rgba(0, 0, 0, 0.7); color: #fff; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.35);">
                    <?= e($itemStatus !== '' ? $itemStatus : 'Off-Plan') ?>
                  </span>
                </span>
                <?php if ($isCompleted): ?>
                  <span class="position-absolute top-0 end-0 p-1">
                    <span class="badge bg-success-subtle text-success border border-success-subtle fs-11">Completed</span>
                  </span>
                <?php endif; ?>
                <?php if ($itemHandover !== '' && !$isCompleted): ?>
                  <span class="position-absolute top-0 end-0 p-1">
                    <span class="badge bg-primary text-white fs-13"><?= e($itemHandover) ?></span>
                  </span>
                <?php endif; ?>
              </div>
              <div class="card-body p-2">
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar bg-light rounded" style="height: 32px; width: 32px;">
                    <iconify-icon icon="solar:home-bold-duotone" class="fs-18 text-primary avatar-title"></iconify-icon>
                  </div>
                  <div>
                    <div class="text-dark fw-medium fs-14"><?= e($itemTitle) ?></div>
                    <p class="text-muted mb-0 fs-13"><?= e($itemDistrict) ?></p>
                  </div>
                </div>
                <div class="row mt-2 g-1">
                  <div class="col-6">
                    <span class="badge bg-light-subtle text-muted border fs-11">
                      <span class="fs-14"><iconify-icon icon="solar:tag-price-broken" class="align-middle"></iconify-icon></span>
                      <?= e($itemPrice) ?>
                    </span>
                  </div>
                  <div class="col-6">
                    <span class="badge bg-light-subtle text-muted border fs-11">
                      <span class="fs-14"><iconify-icon icon="solar:calendar-broken" class="align-middle"></iconify-icon></span>
                      <?= e($itemHandover) ?>
                    </span>
                  </div>
                </div>
              </div>
              <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center border-top py-2 px-2">
                <p class="fw-medium text-dark fs-14 mb-0"><?= e($itemPrice) ?></p>
                <div>
                  <a class="btn btn-light btn-sm" href="<?= e($detailUrl) ?>">View</a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($pages > 1): ?>
        <div class="d-flex justify-content-end">
          <nav aria-label="Page navigation example">
            <ul class="pagination mb-0">
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e($prevUrl) ?>" aria-label="Previous">
                  <span aria-hidden="true">&laquo;</span>
                </a>
              </li>
              <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                <?php $pageUrl = url('external_projects.php') . '?' . build_query(['page' => $p]); ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                  <a class="page-link" href="<?= e($pageUrl) ?>"><?= $p ?></a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e($nextUrl) ?>" aria-label="Next">
                  <span aria-hidden="true">&raquo;</span>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php if ($canRefreshApi): ?>
  <script>
    (function () {
      var btn = document.getElementById('refreshApiBtn');
      var tokenEl = document.getElementById('refreshApiToken');
      var alertEl = document.getElementById('refreshApiAlert');
      if (!btn || !tokenEl || !alertEl) {
        return;
      }
      var apiUrl = <?= json_encode($refreshApiUrl, JSON_UNESCAPED_SLASHES) ?>;
      var cacheUrl = <?= json_encode($refreshUrl, JSON_UNESCAPED_SLASHES) ?>;
      var apiSpinner = btn.querySelector('.spinner-border');
      var labelEl = btn.querySelector('.refresh-api-label');
      function setAlert(type, message) {
        alertEl.innerHTML = '<div class="alert alert-' + type + '">' + message + '</div>';
      }
      btn.addEventListener('click', function () {
        if (btn.disabled) {
          return;
        }
        btn.disabled = true;
        if (apiSpinner) {
          apiSpinner.classList.remove('d-none');
        }
        if (labelEl) {
          labelEl.textContent = 'Refreshing...';
        }
        setAlert('info', 'Sending refresh request...');
        var body = '_csrf=' + encodeURIComponent(tokenEl.value || '');
        fetch(apiUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: body
        })
          .then(function (response) {
            return response.json()
              .then(function (data) { return { status: response.status, data: data }; })
              .catch(function () { return { status: response.status, data: null }; });
          })
          .then(function (payload) {
            var ok = payload.status >= 200 && payload.status < 300;
            var data = payload.data || {};
            if (ok && (data.success || data.ok || data.mode)) {
              setAlert('success', 'Refresh complete. Reloading latest data...');
              setTimeout(function () {
                window.location.href = cacheUrl;
              }, 1200);
              return;
            }
            var message = data.message || data.error || 'Refresh failed.';
            setAlert('danger', message);
          })
          .catch(function () {
            setAlert('danger', 'Refresh failed.');
          })
          .finally(function () {
            btn.disabled = false;
            if (apiSpinner) {
              apiSpinner.classList.add('d-none');
            }
            if (labelEl) {
              labelEl.textContent = 'Refresh Data';
            }
          });
      });
    })();
  </script>
<?php endif; ?>
<script>
  (function () {
    var input = document.getElementById('titleSearchInput');
    var results = document.getElementById('titleSearchResults');
    if (!input || !results) return;
    var titles = <?= json_encode(array_values($titleSuggestions), JSON_UNESCAPED_SLASHES) ?> || [];

    function render(matches) {
      if (!matches.length) {
        results.classList.add('d-none');
        results.innerHTML = '';
        return;
      }
      results.innerHTML = matches.map(function (title) {
        return '<button type="button" class="list-group-item list-group-item-action">' +
          title.replace(/</g, '&lt;').replace(/>/g, '&gt;') +
        '</button>';
      }).join('');
      results.classList.remove('d-none');
    }

    function findMatches(query) {
      var q = query.trim().toLowerCase();
      if (!q) return [];
      return titles.filter(function (title) {
        return title.toLowerCase().indexOf(q) !== -1;
      }).slice(0, 8);
    }

    input.addEventListener('input', function () {
      render(findMatches(input.value));
    });

    results.addEventListener('click', function (event) {
      var target = event.target;
      if (target && target.textContent) {
        input.value = target.textContent.trim();
        results.classList.add('d-none');
      }
    });

    document.addEventListener('click', function (event) {
      if (!results.contains(event.target) && event.target !== input) {
        results.classList.add('d-none');
      }
    });
  })();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../app/Views/layouts/app.php';
?>

<?php
declare(strict_types=1);

require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../includes/external_projects_service.php';

require_role(['ADMIN', 'CEO', 'AGENT']);

$refresh = (string)($_GET['refresh'] ?? '');
$response = fetchExternalProjects($refresh === '1');
$meta = $response['meta'] ?? [];
$projects = $response['projects'] ?? [];

$q = trim((string)($_GET['q'] ?? ''));
$district = trim((string)($_GET['district'] ?? ''));
$handover = trim((string)($_GET['handover'] ?? ''));
$sort = trim((string)($_GET['sort'] ?? 'title'));
$dir = strtolower(trim((string)($_GET['dir'] ?? 'asc')));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 25);
$perPageOptions = [25, 50, 100];
if (!in_array($perPage, $perPageOptions, true)) {
  $perPage = 25;
}

$allowedSorts = ['title', 'price', 'handover'];
if (!in_array($sort, $allowedSorts, true)) {
  $sort = 'title';
}
$dir = $dir === 'desc' ? 'desc' : 'asc';

$qLower = strtolower($q);
$filtered = array_values(array_filter($projects, function (array $item) use ($qLower, $district, $handover): bool {
  $title = (string)($item['title'] ?? '');
  $itemDistrict = (string)($item['district'] ?? '');
  $itemHandover = (string)($item['handover'] ?? '');

  if ($qLower !== '') {
    $haystack = strtolower($title . ' ' . $itemDistrict);
    if (strpos($haystack, $qLower) === false) {
      return false;
    }
  }
  if ($district !== '' && $itemDistrict !== $district) {
    return false;
  }
  if ($handover !== '' && $itemHandover !== $handover) {
    return false;
  }
  return true;
}));

$mult = $dir === 'desc' ? -1 : 1;
usort($filtered, function (array $a, array $b) use ($sort, $mult): int {
  if ($sort === 'price') {
    $aVal = $a['price_amount'] ?? null;
    $bVal = $b['price_amount'] ?? null;
  } elseif ($sort === 'handover') {
    $aVal = $a['handover_key'] ?? null;
    $bVal = $b['handover_key'] ?? null;
  } else {
    $aVal = $a['title'] ?? '';
    $bVal = $b['title'] ?? '';
    $cmp = strcasecmp((string)$aVal, (string)$bVal);
    return $cmp * $mult;
  }

  if ($aVal === null && $bVal === null) return 0;
  if ($aVal === null) return 1;
  if ($bVal === null) return -1;
  return ($aVal <=> $bVal) * $mult;
});

$total = count($filtered);
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) {
  $page = $pages;
}
$offset = ($page - 1) * $perPage;
$paged = array_slice($filtered, $offset, $perPage);

$districtOptions = [];
$handoverOptions = [];
foreach ($projects as $item) {
  $districtValue = trim((string)($item['district'] ?? ''));
  $handoverValue = trim((string)($item['handover'] ?? ''));
  if ($districtValue !== '') $districtOptions[$districtValue] = true;
  if ($handoverValue !== '') $handoverOptions[$handoverValue] = true;
}
$districtOptions = array_keys($districtOptions);
$handoverOptions = array_keys($handoverOptions);
sort($districtOptions, SORT_NATURAL | SORT_FLAG_CASE);
sort($handoverOptions, SORT_NATURAL | SORT_FLAG_CASE);

$title = 'External Projects';
$page_title = 'External Projects';
$page_subtitle = 'External Projects';
$refreshQuery = build_query(['refresh' => 1]);
$refreshUrl = url('external_projects.php') . ($refreshQuery !== '' ? ('?' . $refreshQuery) : '');
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
      Source: <?= e((string)($meta['source'] ?? '')) ?>
      | Last Updated: <?= e((string)($meta['lastUpdated'] ?? '')) ?>
      | Count: <?= e((string)($meta['count'] ?? '')) ?>
      <?php if (!empty($meta['isStale'])): ?>
        | <span class="text-danger">STALE</span>
      <?php endif; ?>
    </div>

    <?php if (!empty($response['warning'])): ?>
      <div class="alert alert-warning"><?= e((string)$response['warning']) ?></div>
    <?php endif; ?>

    <?php if (!$response['ok']): ?>
      <div class="alert alert-danger"><?= e((string)($response['error'] ?? 'Unable to load projects.')) ?></div>
    <?php endif; ?>

    <form method="get" class="row g-2 align-items-end mb-3">
      <div class="col-md-4">
        <label class="form-label">Search</label>
        <input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Search title or district">
      </div>
      <div class="col-md-3">
        <label class="form-label">District</label>
        <select name="district" class="form-select">
          <option value="">All</option>
          <?php foreach ($districtOptions as $option): ?>
            <option value="<?= e($option) ?>" <?= $option === $district ? 'selected' : '' ?>><?= e($option) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Handover</label>
        <select name="handover" class="form-select">
          <option value="">All</option>
          <?php foreach ($handoverOptions as $option): ?>
            <option value="<?= e($option) ?>" <?= $option === $handover ? 'selected' : '' ?>><?= e($option) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Sort</label>
        <div class="d-flex gap-2">
          <select name="sort" class="form-select">
            <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Title</option>
            <option value="price" <?= $sort === 'price' ? 'selected' : '' ?>>Price</option>
            <option value="handover" <?= $sort === 'handover' ? 'selected' : '' ?>>Handover</option>
          </select>
          <select name="dir" class="form-select">
            <option value="asc" <?= $dir === 'asc' ? 'selected' : '' ?>>Asc</option>
            <option value="desc" <?= $dir === 'desc' ? 'selected' : '' ?>>Desc</option>
          </select>
        </div>
      </div>
      <div class="col-md-2">
        <label class="form-label">Per page</label>
        <select name="per_page" class="form-select">
          <?php foreach ($perPageOptions as $opt): ?>
            <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-12">
        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
        <a href="<?= e(url('external_projects.php')) ?>" class="btn btn-light btn-sm">Reset</a>
        <a href="<?= e($refreshUrl) ?>" class="btn btn-outline-secondary btn-sm">Refresh</a>
      </div>
    </form>

    <?php if ($response['ok'] && !$filtered): ?>
      <div class="alert alert-info">No projects found.</div>
    <?php endif; ?>

    <?php if ($response['ok'] && $filtered): ?>
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
          <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="card overflow-hidden h-100">
              <div class="position-relative">
                <?php if (!empty($item['image'])): ?>
                  <?php $imageUrl = (string)$item['image']; ?>
                  <?php $payload = rtrim(strtr(base64_encode($imageUrl), '+/', '-_'), '='); ?>
                  <?php $signature = hash_hmac('sha256', $payload, app_key()); ?>
                  <?php $imageToken = $payload . '.' . $signature; ?>
                  <img src="<?= e(url('image-proxy.php?id=' . $imageToken)) ?>" alt="<?= e((string)($item['title'] ?? '')) ?>" loading="lazy" class="img-fluid rounded-top" style="height: 160px; width: 100%; object-fit: cover;">
                <?php else: ?>
                  <div class="bg-light d-flex align-items-center justify-content-center" style="height: 160px;">
                    <span class="text-muted">No image</span>
                  </div>
                <?php endif; ?>
                <?php if (!empty($item['handover'])): ?>
                  <span class="position-absolute top-0 end-0 p-1">
                    <span class="badge bg-primary text-white fs-13"><?= e((string)$item['handover']) ?></span>
                  </span>
                <?php endif; ?>
              </div>
              <div class="card-body p-2">
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar bg-light rounded" style="height: 32px; width: 32px;">
                    <iconify-icon icon="solar:home-bold-duotone" class="fs-18 text-primary avatar-title"></iconify-icon>
                  </div>
                  <div>
                    <div class="text-dark fw-medium fs-14"><?= e((string)($item['title'] ?? '')) ?></div>
                    <p class="text-muted mb-0 fs-13"><?= e((string)($item['district'] ?? '')) ?></p>
                  </div>
                </div>
                <div class="row mt-2 g-1">
                  <div class="col-6">
                    <span class="badge bg-light-subtle text-muted border fs-11">
                      <span class="fs-14"><iconify-icon icon="solar:tag-price-broken" class="align-middle"></iconify-icon></span>
                      <?= e((string)($item['price'] ?? '')) ?>
                    </span>
                  </div>
                  <div class="col-6">
                    <span class="badge bg-light-subtle text-muted border fs-11">
                      <span class="fs-14"><iconify-icon icon="solar:calendar-broken" class="align-middle"></iconify-icon></span>
                      <?= e((string)($item['handover'] ?? '')) ?>
                    </span>
                  </div>
                </div>
              </div>
              <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center border-top py-2 px-2">
                <p class="fw-medium text-dark fs-14 mb-0"><?= e((string)($item['price'] ?? '')) ?></p>
                <div>
                  <span class="text-muted fs-12"><?= e((string)($item['district'] ?? '')) ?></span>
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
<?php
$content = ob_get_clean();
require __DIR__ . '/../app/Views/layouts/app.php';

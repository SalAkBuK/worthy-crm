<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$items = $items ?? [];
$meta = $meta ?? [];
$filters = $filters ?? [];
$options = $options ?? [];
$user = current_user();
$role = $user['role'] ?? '';
$canEdit = in_array($role, ['ADMIN', 'CEO'], true);
$q = $filters['q'] ?? '';
$projectName = $filters['project_name'] ?? '';
$area = $filters['area'] ?? '';
$developer = $filters['developer'] ?? '';
$propertyType = $filters['property_type'] ?? '';
$bedrooms = $filters['bedrooms'] ?? '';
$status = $filters['status'] ?? '';
$listingType = $filters['listing_type'] ?? '';
$sort = $filters['sort'] ?? '';
$dir = $filters['dir'] ?? 'desc';
$developers = $options['developers'] ?? [];
$statuses = $options['statuses'] ?? [];
$propertyTypes = $options['property_types'] ?? [];
$listingTypes = $options['listing_types'] ?? [];
$bedroomOptions = $options['bedrooms'] ?? [];
$projects = $options['projects'] ?? [];
$returnPath = $_SERVER['REQUEST_URI'] ?? 'listings';
$formatAedShort = static function ($value): string {
  if ($value === null || $value === '') return '-';
  $num = (float)$value;
  if ($num >= 1000000000) return 'AED ' . rtrim(rtrim(number_format($num / 1000000000, 2, '.', ''), '0'), '.') . 'B';
  if ($num >= 1000000) return 'AED ' . rtrim(rtrim(number_format($num / 1000000, 2, '.', ''), '0'), '.') . 'M';
  if ($num >= 1000) return 'AED ' . rtrim(rtrim(number_format($num / 1000, 2, '.', ''), '0'), '.') . 'K';
  return 'AED ' . number_format($num, 0, '.', ',');
};
?>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <h4 class="card-title mb-1">Listings</h4>
            <div class="text-muted fs-12">Manual listings (basic).</div>
          </div>
          <?php if ($canEdit): ?>
            <a class="btn btn-primary" href="<?= e(url('listings/create')) ?>">Add Listing</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-body border-top">
        <form class="row g-2" method="get" action="<?= e(url('listings')) ?>">
          <div class="col-md-3">
            <label class="form-label">Search</label>
            <input class="form-control" type="text" name="q" value="<?= e($q) ?>" placeholder="Project / Area / Developer">
          </div>
          <div class="col-md-2">
            <label class="form-label">Project</label>
            <select class="form-select" name="project_name">
              <option value="">All</option>
              <?php foreach ($projects as $option): ?>
                <option value="<?= e($option) ?>" <?= $option === $projectName ? 'selected' : '' ?>><?= e($option) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Area</label>
            <input class="form-control" type="text" name="area" value="<?= e($area) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Developer</label>
            <select class="form-select" name="developer">
              <option value="">All</option>
              <?php foreach ($developers as $option): ?>
                <option value="<?= e($option) ?>" <?= $option === $developer ? 'selected' : '' ?>><?= e($option) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Apartment Type</label>
            <select class="form-select" name="property_type">
              <option value="">All</option>
              <?php foreach ($propertyTypes as $option): ?>
                <option value="<?= e($option) ?>" <?= $option === $propertyType ? 'selected' : '' ?>><?= e($option) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-1">
            <label class="form-label">Bedrooms</label>
            <select class="form-select" name="bedrooms">
              <option value="">All</option>
              <?php foreach ($bedroomOptions as $option): ?>
                <?php
                  $value = (string)$option;
                  $label = ctype_digit($value) ? ($value . ' BDR') : $value;
                ?>
                <option value="<?= e($value) ?>" <?= $value === $bedrooms ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-1">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
              <option value="">All</option>
              <?php foreach ($statuses as $option): ?>
                <option value="<?= e($option) ?>" <?= $option === $status ? 'selected' : '' ?>><?= e($option) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-1">
            <label class="form-label">Listing Type</label>
            <select class="form-select" name="listing_type">
              <option value="">All</option>
              <?php foreach ($listingTypes as $option): ?>
                <option value="<?= e($option) ?>" <?= $option === $listingType ? 'selected' : '' ?>><?= e($option) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Filter</button>
            <a class="btn btn-outline-light" href="<?= e(url('listings')) ?>">Clear</a>
          </div>
        </form>
      </div>
    </div>
    <div class="card">
      <div class="card-body">
        <style>
          .table-scroll-top {
            overflow-x: auto;
            overflow-y: hidden;
            height: 14px;
          }
          .table-scroll-top-inner {
            height: 1px;
          }
        </style>
        <div class="table-scroll-top mb-2" data-sync="listings-table">
          <div class="table-scroll-top-inner"></div>
        </div>
        <div class="table-responsive">
          <table class="table align-middle text-nowrap mb-0">
            <thead class="bg-light-subtle">
              <tr>
                <th>Project</th>
                <th>Unit</th>
                <th>Developer</th>
                <th>Bedrooms</th>
                <th>Status</th>
                <th>Apartment Type</th>
                <?php
                  $isPriceSort = $sort === 'price_amount';
                  $dirLower = strtolower((string)$dir);
                ?>
                <th>
                  <span class="me-1">Price</span>
                  <a class="text-decoration-none" href="?<?= e(build_query(['sort' => 'price_amount', 'dir' => 'asc'])) ?>" aria-label="Sort price ascending">
                    <i class="ri-arrow-up-s-line <?= $isPriceSort && $dirLower === 'asc' ? '' : 'text-muted' ?>"></i>
                  </a>
                  <a class="text-decoration-none ms-1" href="?<?= e(build_query(['sort' => 'price_amount', 'dir' => 'desc'])) ?>" aria-label="Sort price descending">
                    <i class="ri-arrow-down-s-line <?= $isPriceSort && $dirLower === 'desc' ? '' : 'text-muted' ?>"></i>
                  </a>
                </th>
                <th>Listing Type</th>
                <th>Area</th>
                <th>Location</th>
                <th>Brochure</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$items): ?>
                <tr>
                  <td colspan="12" class="text-center text-muted py-4">No listings found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($items as $row): ?>
                  <?php
                    $priceAmount = $row['price_amount'] ?? null;
                    $priceLabel = $row['price_display_label'] ?? 'Price';
                    if ($priceLabel === '') $priceLabel = 'Price';
                    $priceText = '-';
                    $priceFullText = '';
                    if ($priceAmount !== null && $priceAmount !== '') {
                      $priceText = $formatAedShort($priceAmount);
                      $priceFullText = 'AED ' . number_format((float)$priceAmount, 0, '.', ',');
                    } elseif (!empty($row['price_raw'])) {
                      $parsedRaw = parse_aed_amount((string)$row['price_raw']);
                      if ($parsedRaw !== null) {
                        $priceText = $formatAedShort($parsedRaw);
                        $priceFullText = 'AED ' . number_format((float)$parsedRaw, 0, '.', ',');
                      } else {
                        $priceText = (string)$row['price_raw'];
                      }
                    }
                    $priceVariants = '';
                    $priceVariantsTitle = '';
                    if (!empty($row['price_furnished_amount']) && !empty($row['price_unfurnished_amount'])) {
                      $furnishedText = $formatAedShort($row['price_furnished_amount']);
                      $unfurnishedText = $formatAedShort($row['price_unfurnished_amount']);
                      $priceVariants = 'F: ' . $furnishedText . ' | U: ' . $unfurnishedText;
                      $priceVariantsTitle = 'F: AED ' . number_format((float)$row['price_furnished_amount'], 0, '.', ',')
                        . ' | U: AED ' . number_format((float)$row['price_unfurnished_amount'], 0, '.', ',');
                    }
                    $location = '-';
                    $notes = $row['notes'] ?? '';
                    if (is_string($notes) && stripos($notes, 'location:') === 0) {
                      $location = trim(substr($notes, strlen('location:')));
                      if ($location === '') $location = '-';
                    }
                  ?>
                  <tr>
                    <td><?= e($row['project_name'] ?? '-') ?></td>
                    <td><?= e($row['unit_ref'] ?? '-') ?></td>
                    <td><?= e($row['developer'] ?? '-') ?></td>
                    <td><?= e($row['beds_raw'] ?? ($row['beds'] !== null ? (string)$row['beds'] : '-')) ?></td>
                    <td><?= e($row['status'] ?? '-') ?></td>
                    <td><?= e($row['property_type'] ?? '-') ?></td>
                    <td>
                      <div class="d-flex align-items-center gap-1">
                        <span<?= $priceFullText !== '' ? ' title="' . e($priceFullText) . '"' : '' ?>><?= e($priceText) ?></span>
                        <span class="badge bg-light text-dark border"><?= e($priceLabel) ?></span>
                      </div>
                      <?php if ($priceVariants !== ''): ?>
                        <div class="text-muted fs-12" title="<?= e($priceVariantsTitle) ?>"><?= e($priceVariants) ?></div>
                      <?php endif; ?>
                    </td>
                    <td><?= e($row['listing_type'] ?? '-') ?></td>
                    <td><?= e($row['area'] ?? '-') ?></td>
                    <td><?= e($location) ?></td>
                    <td>
                      <?php if (!empty($row['brochure_url'])): ?>
                        <a href="<?= e($row['brochure_url']) ?>" target="_blank" rel="noopener">Open</a>
                      <?php else: ?>
                        -
                      <?php endif; ?>
                    </td>
                    <td class="text-end">
                      <a class="btn btn-light btn-sm" href="<?= e(url('listings/show?id=' . $row['id'])) ?>">View</a>
                      <?php if ($canEdit): ?>
                        <a class="btn btn-soft-primary btn-sm" href="<?= e(url('listings/edit?id=' . $row['id'])) ?>">Edit</a>
                        <form class="d-inline" method="post" action="<?= e(url('listings/delete')) ?>" onsubmit="return confirm('Delete this listing? This cannot be undone.');">
                          <?= csrf_field() ?>
                          <input type="hidden" name="id" value="<?= e((string)$row['id']) ?>">
                          <input type="hidden" name="return" value="<?= e($returnPath) ?>">
                          <button class="btn btn-soft-danger btn-sm" type="submit">Delete</button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if (!empty($meta)): ?>
          <div class="text-muted fs-12 mt-3">
            Total: <?= e((string)($meta['total'] ?? count($items))) ?>
          </div>
        <?php endif; ?>
        <div class="text-muted fs-12 mt-2">
          Legend: K = Thousand, M = Million, B = Billion. Prices are shown in AED.
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  (function() {
    var top = document.querySelector('.table-scroll-top[data-sync="listings-table"]');
    var bottom = document.querySelector('.table-responsive');
    if (!top || !bottom) return;
    var inner = top.querySelector('.table-scroll-top-inner');
    var table = bottom.querySelector('table');
    if (!inner || !table) return;

    var syncing = false;
    var syncTop = function() {
      if (syncing) return;
      syncing = true;
      bottom.scrollLeft = top.scrollLeft;
      syncing = false;
    };
    var syncBottom = function() {
      if (syncing) return;
      syncing = true;
      top.scrollLeft = bottom.scrollLeft;
      syncing = false;
    };
    var refresh = function() {
      var width = table.scrollWidth;
      inner.style.width = width + 'px';
      top.style.display = width > bottom.clientWidth + 1 ? 'block' : 'none';
    };

    top.addEventListener('scroll', syncTop);
    bottom.addEventListener('scroll', syncBottom);
    window.addEventListener('resize', refresh);
    setTimeout(refresh, 0);
  })();
</script>

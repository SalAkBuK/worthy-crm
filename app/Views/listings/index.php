<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$items = $items ?? [];
$meta = $meta ?? [];
$filters = $filters ?? [];
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
            <input class="form-control" type="text" name="project_name" value="<?= e($projectName) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Area</label>
            <input class="form-control" type="text" name="area" value="<?= e($area) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Developer</label>
            <input class="form-control" type="text" name="developer" value="<?= e($developer) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Apartment Type</label>
            <input class="form-control" type="text" name="property_type" value="<?= e($propertyType) ?>">
          </div>
          <div class="col-md-1">
            <label class="form-label">Bedrooms</label>
            <input class="form-control" type="text" name="bedrooms" value="<?= e($bedrooms) ?>" placeholder="1BDR">
          </div>
          <div class="col-md-1">
            <label class="form-label">Status</label>
            <input class="form-control" type="text" name="status" value="<?= e($status) ?>">
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
                <th>Price</th>
                <th>Area</th>
                <th>Location</th>
                <th>Brochure</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$items): ?>
                <tr>
                  <td colspan="10" class="text-center text-muted py-4">No listings found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($items as $row): ?>
                  <?php
                    $price = $row['price_amount'] ?? '';
                    if ($price === '' || $price === null) $price = $row['price_raw'] ?? '-';
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
                    <td><?= e((string)$price) ?></td>
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
      </div>
    </div>
  </div>
</div>

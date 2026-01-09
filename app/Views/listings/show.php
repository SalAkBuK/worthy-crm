<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$listing = $listing ?? [];
$user = current_user();
$role = $user['role'] ?? '';
$canEdit = in_array($role, ['ADMIN', 'CEO'], true);
?>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <h4 class="card-title mb-1">Listing Details</h4>
            <div class="text-muted fs-12">ID: <?= e((string)($listing['id'] ?? '')) ?></div>
          </div>
          <div class="d-flex gap-2">
            <?php if ($canEdit && !empty($listing['id'])): ?>
              <a class="btn btn-soft-primary" href="<?= e(url('listings/edit?id=' . $listing['id'])) ?>">Edit</a>
            <?php endif; ?>
            <a class="btn btn-outline-light" href="<?= e(url('listings')) ?>">Back</a>
          </div>
        </div>
      </div>
      <div class="card-body border-top">
        <div class="table-responsive">
          <table class="table table-sm">
            <tbody>
              <?php foreach ($listing as $key => $value): ?>
                <tr>
                  <th class="text-muted" style="width: 220px;"><?= e((string)$key) ?></th>
                  <td><?= e(is_scalar($value) || $value === null ? (string)$value : json_encode($value)) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

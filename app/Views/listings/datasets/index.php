<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../Helpers/functions.php';
$items = $items ?? [];
$meta = $meta ?? [];
?>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <h4 class="card-title mb-1">Listing Datasets</h4>
            <div class="text-muted fs-12">Uploaded PDF/CSV datasets.</div>
          </div>
          <a class="btn btn-primary" href="<?= e(url('listings/datasets/upload')) ?>">Upload PDF</a>
        </div>
      </div>
      <div class="card-body border-top">
        <div class="table-responsive">
          <table class="table align-middle text-nowrap mb-0">
            <thead class="bg-light-subtle">
              <tr>
                <th>ID</th>
                <th>Original File</th>
                <th>Status</th>
                <th>Parsed</th>
                <th>Failed</th>
                <th>Created</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$items): ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">No datasets uploaded.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($items as $row): ?>
                  <tr>
                    <td><?= e((string)$row['id']) ?></td>
                    <td><?= e($row['original_filename'] ?? '-') ?></td>
                    <td><?= e($row['status'] ?? '-') ?></td>
                    <td><?= e((string)($row['parsed_count'] ?? 0)) ?></td>
                    <td><?= e((string)($row['failed_count'] ?? 0)) ?></td>
                    <td><?= e($row['created_at'] ?? '-') ?></td>
                    <td class="text-end">
                      <a class="btn btn-light btn-sm" href="<?= e(url('listings/datasets/show?id=' . $row['id'])) ?>">View</a>
                      <a class="btn btn-soft-primary btn-sm" href="<?= e(url('listings/datasets/download?id=' . $row['id'])) ?>">Download</a>
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

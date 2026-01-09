<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../Helpers/functions.php';
$dataset = $dataset ?? [];
$preview = $preview ?? '';
$listingsCount = $listings_count ?? 0;
?>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <h4 class="card-title mb-1">Dataset Details</h4>
            <div class="text-muted fs-12">ID: <?= e((string)($dataset['id'] ?? '')) ?></div>
          </div>
          <div class="d-flex gap-2">
            <?php if (!empty($dataset['id'])): ?>
              <a class="btn btn-soft-primary" href="<?= e(url('listings/datasets/download?id=' . $dataset['id'])) ?>">Download</a>
            <?php endif; ?>
            <a class="btn btn-outline-light" href="<?= e(url('listings/datasets')) ?>">Back</a>
          </div>
        </div>
      </div>
      <div class="card-body border-top">
        <div class="row mb-3">
          <div class="col-md-4">
            <div class="card border">
              <div class="card-body">
                <div class="text-muted fs-12">Listings Created</div>
                <div class="fs-20 fw-semibold"><?= e((string)$listingsCount) ?></div>
              </div>
            </div>
          </div>
          <div class="col-md-8">
            <div class="card border">
              <div class="card-body">
                <div class="text-muted fs-12">Status</div>
                <div class="fs-20 fw-semibold"><?= e($dataset['status'] ?? '-') ?></div>
              </div>
            </div>
          </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mb-3">
          <?php if (!empty($dataset['id'])): ?>
            <form method="post" action="<?= e(url('listings/datasets/import')) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="dataset_id" value="<?= e((string)$dataset['id']) ?>">
              <button class="btn btn-soft-warning" type="submit"
                onclick="return confirm('Re-import this dataset? This may create duplicate listings.');">
                Re-import
              </button>
            </form>
            <form method="post" action="<?= e(url('listings/datasets/delete')) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= e((string)$dataset['id']) ?>">
              <button class="btn btn-soft-danger" type="submit"
                onclick="return confirm('Delete this dataset and all listings created from it? This cannot be undone.');">
                Delete Dataset
              </button>
            </form>
          <?php endif; ?>
        </div>
        <div class="table-responsive">
          <table class="table table-sm">
            <tbody>
              <?php foreach ($dataset as $key => $value): ?>
                <tr>
                  <th class="text-muted" style="width: 220px;"><?= e((string)$key) ?></th>
                  <td><?= e(is_scalar($value) || $value === null ? (string)$value : json_encode($value)) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          <h5 class="card-title mb-2">Extracted Text Preview</h5>
          <?php if ($preview === ''): ?>
            <div class="text-muted">No extracted text available.</div>
          <?php else: ?>
            <pre class="mb-0" style="white-space: pre-wrap;"><?= e($preview) ?></pre>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

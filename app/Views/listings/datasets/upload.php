<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../Helpers/functions.php';
?>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-3">Upload Dataset</h4>
        <div class="row g-3">
          <div class="col-12 col-lg-6">
            <form method="post" action="<?= e(url('listings/datasets/import')) ?>" enctype="multipart/form-data">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">PDF File *</label>
                <input class="form-control" type="file" name="dataset_pdf" accept="application/pdf" required>
                <div class="form-text text-muted">Max size 15MB. PDF only.</div>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Upload PDF</button>
                <a class="btn btn-outline-light" href="<?= e(url('listings/datasets')) ?>">Cancel</a>
              </div>
            </form>
          </div>
          <div class="col-12 col-lg-6">
            <form method="post" action="<?= e(url('listings/datasets/import')) ?>" enctype="multipart/form-data">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">CSV File *</label>
                <input class="form-control" type="file" name="dataset_csv" accept=".csv,text/csv" required>
                <div class="form-text text-muted">Max size 15MB. Headers must match the template.</div>
              </div>
              <div class="mb-3">
                <div class="form-text text-muted">
                  Headers: project_name, area, developer, unit_ref, property_type, beds_raw, beds, baths_raw, baths,
                  size_raw, size_sqft, price_raw, price_amount, status, payment_plan, brochure_url, maps_url, media_url, notes
                  (also accepts: Project, Unit, Status, Price, Area, Location, Bedrooms, Listing_URL)
                </div>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Upload CSV</button>
                <a class="btn btn-outline-light" href="<?= e(url('listings/datasets')) ?>">Cancel</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

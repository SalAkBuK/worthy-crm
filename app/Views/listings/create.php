<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
?>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-3">Create Listing</h4>
        <form method="post" action="<?= e(url('listings/store')) ?>">
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Project Name *</label>
              <input class="form-control" type="text" name="project_name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Area *</label>
              <input class="form-control" type="text" name="area" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Developer</label>
              <input class="form-control" type="text" name="developer">
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <input class="form-control" type="text" name="status">
            </div>
            <div class="col-md-4">
              <label class="form-label">Unit Ref</label>
              <input class="form-control" type="text" name="unit_ref">
            </div>
            <div class="col-md-4">
              <label class="form-label">Property Type</label>
              <input class="form-control" type="text" name="property_type">
            </div>
            <div class="col-md-4">
              <label class="form-label">Beds (Raw)</label>
              <input class="form-control" type="text" name="beds_raw">
            </div>
            <div class="col-md-2">
              <label class="form-label">Beds</label>
              <input class="form-control" type="number" name="beds" min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Baths (Raw)</label>
              <input class="form-control" type="text" name="baths_raw">
            </div>
            <div class="col-md-2">
              <label class="form-label">Baths</label>
              <input class="form-control" type="number" name="baths" min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Size (Raw)</label>
              <input class="form-control" type="text" name="size_raw">
            </div>
            <div class="col-md-4">
              <label class="form-label">Size (sqft)</label>
              <input class="form-control" type="number" step="0.01" name="size_sqft">
            </div>
            <div class="col-md-4">
              <label class="form-label">Price (Raw)</label>
              <input class="form-control" type="text" name="price_raw">
            </div>
            <div class="col-md-4">
              <label class="form-label">Price (AED)</label>
              <input class="form-control" type="number" step="0.01" name="price_amount">
            </div>
            <div class="col-md-12">
              <label class="form-label">Payment Plan</label>
              <textarea class="form-control" name="payment_plan" rows="2"></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Brochure URL</label>
              <input class="form-control" type="text" name="brochure_url">
            </div>
            <div class="col-md-4">
              <label class="form-label">Maps URL</label>
              <input class="form-control" type="text" name="maps_url">
            </div>
            <div class="col-md-4">
              <label class="form-label">Media URL</label>
              <input class="form-control" type="text" name="media_url">
            </div>
            <div class="col-md-12">
              <label class="form-label">Notes</label>
              <textarea class="form-control" name="notes" rows="3"></textarea>
            </div>
          </div>
          <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Save Listing</button>
            <a class="btn btn-outline-light" href="<?= e(url('listings')) ?>">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

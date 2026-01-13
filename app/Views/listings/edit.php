<?php
declare(strict_types=1);
require_once __DIR__ . '/../../Helpers/functions.php';
$listing = $listing ?? [];
$details = [];
$detailsRaw = $listing['details_json'] ?? '';
if (is_string($detailsRaw) && $detailsRaw !== '') {
  $decoded = json_decode($detailsRaw, true);
  if (is_array($decoded)) $details = $decoded;
}
$view = $details['view'] ?? '';
$handover = $details['handover'] ?? '';
$amenities = $details['amenities'] ?? '';
$features = $details['features'] ?? '';
$amenitiesValue = is_array($amenities) ? implode(', ', $amenities) : (string)$amenities;
$featuresValue = is_array($features) ? implode(', ', $features) : (string)$features;
$paymentPlan = $listing['payment_plan'] ?? '';
if ($paymentPlan === '' && isset($details['payment_plan'])) $paymentPlan = (string)$details['payment_plan'];
?>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-3">Edit Listing</h4>
        <form method="post" action="<?= e(url('listings/update')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= e((string)($listing['id'] ?? '')) ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Project Name *</label>
              <input class="form-control" type="text" name="project_name" required
                value="<?= e($listing['project_name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Area *</label>
              <input class="form-control" type="text" name="area" required
                value="<?= e($listing['area'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Developer</label>
              <input class="form-control" type="text" name="developer"
                value="<?= e($listing['developer'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <input class="form-control" type="text" name="status"
                value="<?= e($listing['status'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Unit Ref</label>
              <input class="form-control" type="text" name="unit_ref"
                value="<?= e($listing['unit_ref'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Property Type</label>
              <input class="form-control" type="text" name="property_type"
                value="<?= e($listing['property_type'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Beds (Raw)</label>
              <input class="form-control" type="text" name="beds_raw"
                value="<?= e($listing['beds_raw'] ?? '') ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">Beds</label>
              <input class="form-control" type="number" name="beds" min="0"
                value="<?= e((string)($listing['beds'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Baths (Raw)</label>
              <input class="form-control" type="text" name="baths_raw"
                value="<?= e($listing['baths_raw'] ?? '') ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">Baths</label>
              <input class="form-control" type="number" name="baths" min="0"
                value="<?= e((string)($listing['baths'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Size (Raw)</label>
              <input class="form-control" type="text" name="size_raw"
                value="<?= e($listing['size_raw'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Size (sqft)</label>
              <input class="form-control" type="number" step="0.01" name="size_sqft"
                value="<?= e((string)($listing['size_sqft'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Price (Raw)</label>
              <input class="form-control" type="text" name="price_raw"
                value="<?= e($listing['price_raw'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Price (AED)</label>
              <input class="form-control" type="number" step="0.01" name="price_amount"
                value="<?= e((string)($listing['price_amount'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Listing Type</label>
              <select class="form-select" name="listing_type">
                <option value="">Select</option>
                <option value="SALE" <?= (($listing['listing_type'] ?? '') === 'SALE') ? 'selected' : '' ?>>Sale</option>
                <option value="RENT" <?= (($listing['listing_type'] ?? '') === 'RENT') ? 'selected' : '' ?>>Rent</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Furnished Price (AED)</label>
              <input class="form-control" type="text" name="price_furnished_raw"
                value="<?= e($listing['price_furnished_raw'] ?? (string)($listing['price_furnished_amount'] ?? '')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Unfurnished Price (AED)</label>
              <input class="form-control" type="text" name="price_unfurnished_raw"
                value="<?= e($listing['price_unfurnished_raw'] ?? (string)($listing['price_unfurnished_amount'] ?? '')) ?>">
            </div>
            <div class="col-md-12">
              <label class="form-label">Payment Plan</label>
              <textarea class="form-control" name="payment_plan" rows="2"><?= e($paymentPlan) ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">View</label>
              <input class="form-control" type="text" name="view"
                value="<?= e($view) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Handover</label>
              <input class="form-control" type="text" name="handover"
                value="<?= e($handover) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Amenities (comma separated)</label>
              <input class="form-control" type="text" name="amenities"
                value="<?= e($amenitiesValue) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Features (comma separated)</label>
              <input class="form-control" type="text" name="features"
                value="<?= e($featuresValue) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Brochure URL</label>
              <input class="form-control" type="text" name="brochure_url"
                value="<?= e($listing['brochure_url'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Maps URL</label>
              <input class="form-control" type="text" name="maps_url"
                value="<?= e($listing['maps_url'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Media URL</label>
              <input class="form-control" type="text" name="media_url"
                value="<?= e($listing['media_url'] ?? '') ?>">
            </div>
            <div class="col-md-12">
              <label class="form-label">Notes</label>
              <textarea class="form-control" name="notes" rows="3"><?= e($listing['notes'] ?? '') ?></textarea>
            </div>
          </div>
          <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Update Listing</button>
            <a class="btn btn-outline-light" href="<?= e(url('listings/show?id=' . ($listing['id'] ?? ''))) ?>">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Models\Listing;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;

final class ListingsController extends BaseController {
  public function index(): void {
    try {
      AuthMiddleware::requireRole(['ADMIN', 'CEO', 'AGENT']);
      $filters = [
        'q' => trim((string)($_GET['q'] ?? '')),
        'project_name' => trim((string)($_GET['project_name'] ?? '')),
        'area' => trim((string)($_GET['area'] ?? '')),
        'developer' => trim((string)($_GET['developer'] ?? '')),
        'property_type' => trim((string)($_GET['property_type'] ?? '')),
        'bedrooms' => trim((string)($_GET['bedrooms'] ?? '')),
        'status' => trim((string)($_GET['status'] ?? '')),
        'listing_type' => trim((string)($_GET['listing_type'] ?? '')),
        'source' => trim((string)($_GET['source'] ?? '')),
        'dataset_id' => $_GET['dataset_id'] ?? '',
        'sort' => trim((string)($_GET['sort'] ?? '')),
        'dir' => trim((string)($_GET['dir'] ?? '')),
      ];
      $options = Listing::getFilterOptions();
      $propertyTypeDefaults = ['Apartment', 'Villa', 'Duplex'];
      $extraPropertyTypes = array_values(array_diff($options['property_types'], $propertyTypeDefaults));
      sort($extraPropertyTypes, SORT_NATURAL | SORT_FLAG_CASE);
      $options['property_types'] = array_values(array_merge($propertyTypeDefaults, $extraPropertyTypes));

      $page = max(1, (int)($_GET['page'] ?? 1));
      $perPage = 100;
      $result = Listing::search($filters, $page, $perPage);
      View::render('listings/index', [
        'title' => 'Listings',
        'filters' => $filters,
        'items' => $result['items'],
        'meta' => $result['meta'],
        'options' => $options,
      ]);
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function show(): void {
    try {
      AuthMiddleware::requireRole(['ADMIN', 'CEO', 'AGENT']);
      $id = (int)($_GET['id'] ?? 0);
      if ($id <= 0) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      $listing = Listing::findById($id);
      if (!$listing) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      View::render('listings/show', [
        'title' => 'Listing Details',
        'listing' => $listing,
      ]);
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function create(): void {
    try {
      AuthMiddleware::requireRole(['ADMIN', 'CEO']);
      View::render('listings/create', [
        'title' => 'Create Listing',
      ]);
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function store(): void {
    try {
      AuthMiddleware::requireRole(['ADMIN', 'CEO']);
      CsrfMiddleware::verify();

      $projectName = trim((string)($_POST['project_name'] ?? ''));
      $area = trim((string)($_POST['area'] ?? ''));
      if ($projectName === '' || $area === '') {
        flash('danger', 'Project name and area are required.');
        redirect('listings/create');
      }

      $priceData = Listing::resolvePriceData([
        'price_raw' => $_POST['price_raw'] ?? null,
        'price_amount' => $_POST['price_amount'] ?? null,
        'price_furnished_raw' => $_POST['price_furnished_raw'] ?? null,
        'price_unfurnished_raw' => $_POST['price_unfurnished_raw'] ?? null,
        'price_furnished_amount' => $_POST['price_furnished_amount'] ?? null,
        'price_unfurnished_amount' => $_POST['price_unfurnished_amount'] ?? null,
      ]);
      $details = $this->buildDetailsPayload($_POST);

      $id = Listing::create([
        'project_name' => $projectName,
        'area' => $area,
        'developer' => $_POST['developer'] ?? null,
        'unit_ref' => $_POST['unit_ref'] ?? null,
        'property_type' => $_POST['property_type'] ?? null,
        'beds_raw' => $_POST['beds_raw'] ?? null,
        'beds' => $_POST['beds'] ?? null,
        'baths_raw' => $_POST['baths_raw'] ?? null,
        'baths' => $_POST['baths'] ?? null,
        'size_raw' => $_POST['size_raw'] ?? null,
        'size_sqft' => $_POST['size_sqft'] ?? null,
        'price_raw' => $priceData['price_raw'] ?? null,
        'price_amount' => $priceData['price_amount'] ?? null,
        'price_furnished_raw' => $priceData['price_furnished_raw'] ?? null,
        'price_unfurnished_raw' => $priceData['price_unfurnished_raw'] ?? null,
        'price_furnished_amount' => $priceData['price_furnished_amount'] ?? null,
        'price_unfurnished_amount' => $priceData['price_unfurnished_amount'] ?? null,
        'price_display_type' => $priceData['price_display_type'] ?? null,
        'price_display_label' => $priceData['price_display_label'] ?? null,
        'listing_type' => $_POST['listing_type'] ?? null,
        'status' => $_POST['status'] ?? null,
        'payment_plan' => $_POST['payment_plan'] ?? null,
        'brochure_url' => $_POST['brochure_url'] ?? null,
        'maps_url' => $_POST['maps_url'] ?? null,
        'media_url' => $_POST['media_url'] ?? null,
        'notes' => $_POST['notes'] ?? null,
        'details_json' => $details,
        'source' => 'MANUAL',
        'dataset_id' => null,
        'raw_data' => null,
        'created_by_user_id' => current_user()['id'] ?? null,
      ]);

      flash('success', 'Listing created.');
      redirect('listings/show?id=' . $id);
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function edit(): void {
    try {
      AuthMiddleware::requireRole(['ADMIN', 'CEO']);
      $id = (int)($_GET['id'] ?? 0);
      if ($id <= 0) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      $listing = Listing::findById($id);
      if (!$listing) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      View::render('listings/edit', [
        'title' => 'Edit Listing',
        'listing' => $listing,
      ]);
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function update(): void {
    try {
      AuthMiddleware::requireRole(['ADMIN', 'CEO']);
      CsrfMiddleware::verify();
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      $listing = Listing::findById($id);
      if (!$listing) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }

      $projectName = trim((string)($_POST['project_name'] ?? ''));
      $area = trim((string)($_POST['area'] ?? ''));
      if ($projectName === '' || $area === '') {
        flash('danger', 'Project name and area are required.');
        redirect('listings/edit?id=' . $id);
      }

      $priceData = Listing::resolvePriceData([
        'price_raw' => $_POST['price_raw'] ?? null,
        'price_amount' => $_POST['price_amount'] ?? null,
        'price_furnished_raw' => $_POST['price_furnished_raw'] ?? null,
        'price_unfurnished_raw' => $_POST['price_unfurnished_raw'] ?? null,
        'price_furnished_amount' => $_POST['price_furnished_amount'] ?? null,
        'price_unfurnished_amount' => $_POST['price_unfurnished_amount'] ?? null,
      ]);
      $details = $this->buildDetailsPayload($_POST);

      Listing::update($id, [
        'project_name' => $projectName,
        'area' => $area,
        'developer' => $_POST['developer'] ?? null,
        'unit_ref' => $_POST['unit_ref'] ?? null,
        'property_type' => $_POST['property_type'] ?? null,
        'beds_raw' => $_POST['beds_raw'] ?? null,
        'beds' => $_POST['beds'] ?? null,
        'baths_raw' => $_POST['baths_raw'] ?? null,
        'baths' => $_POST['baths'] ?? null,
        'size_raw' => $_POST['size_raw'] ?? null,
        'size_sqft' => $_POST['size_sqft'] ?? null,
        'price_raw' => $priceData['price_raw'] ?? null,
        'price_amount' => $priceData['price_amount'] ?? null,
        'price_furnished_raw' => $priceData['price_furnished_raw'] ?? null,
        'price_unfurnished_raw' => $priceData['price_unfurnished_raw'] ?? null,
        'price_furnished_amount' => $priceData['price_furnished_amount'] ?? null,
        'price_unfurnished_amount' => $priceData['price_unfurnished_amount'] ?? null,
        'price_display_type' => $priceData['price_display_type'] ?? null,
        'price_display_label' => $priceData['price_display_label'] ?? null,
        'listing_type' => $_POST['listing_type'] ?? null,
        'status' => $_POST['status'] ?? null,
        'payment_plan' => $_POST['payment_plan'] ?? null,
        'brochure_url' => $_POST['brochure_url'] ?? null,
        'maps_url' => $_POST['maps_url'] ?? null,
        'media_url' => $_POST['media_url'] ?? null,
        'notes' => $_POST['notes'] ?? null,
        'details_json' => $details,
        'source' => 'MANUAL',
        'dataset_id' => null,
        'raw_data' => null,
      ]);

      flash('success', 'Listing updated.');
      redirect('listings/show?id=' . $id);
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function delete(): void {
    try {
      AuthMiddleware::requireRole(['ADMIN', 'CEO']);
      CsrfMiddleware::verify();
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      $listing = Listing::findById($id);
      if (!$listing) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }

      $returnPath = (string)($_POST['return'] ?? 'listings');
      if (str_starts_with($returnPath, 'http')) {
        $returnPath = 'listings';
      } else {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
        if ($base !== '' && str_starts_with($returnPath, $base)) {
          $returnPath = substr($returnPath, strlen($base));
        }
      }
      $safeReturn = ltrim($returnPath, '/');
      if ($safeReturn === '') $safeReturn = 'listings';

      $deleted = Listing::delete($id);
      if ($deleted <= 0) {
        flash('danger', 'Failed to delete listing.');
        redirect($safeReturn);
      }

      AuditLog::log((int)(current_user()['id'] ?? 0), 'LISTING_DELETE', [
        'listing_id' => $id,
        'project_name' => $listing['project_name'] ?? null,
        'area' => $listing['area'] ?? null,
      ]);
      $recipients = User::userIdsByRoles(['ADMIN', 'CEO']);
      Notification::createMany(
        $recipients,
        'listing_deleted',
        'Listing deleted',
        'Listing "' . ($listing['project_name'] ?? 'Unknown') . '" was deleted.',
        'listings',
        ['listing_id' => $id]
      );

      flash('success', 'Listing deleted.');
      redirect($safeReturn);
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  private function buildDetailsPayload(array $input): ?array {
    $details = [];
    $handover = trim((string)($input['handover'] ?? ''));
    $view = trim((string)($input['view'] ?? ''));
    $amenities = $this->parseList($input['amenities'] ?? null);
    $features = $this->parseList($input['features'] ?? null);
    $paymentPlan = trim((string)($input['payment_plan'] ?? ''));

    if ($handover !== '') $details['handover'] = $handover;
    if ($view !== '') $details['view'] = $view;
    if ($amenities) $details['amenities'] = $amenities;
    if ($features) $details['features'] = $features;
    if ($paymentPlan !== '') $details['payment_plan'] = $paymentPlan;
    if (!empty($input['media_url'])) $details['media_note'] = 'Click to Open Media File';

    return $details ?: null;
  }

  private function parseList($value): array {
    if ($value === null) return [];
    $value = trim((string)$value);
    if ($value === '') return [];
    $parts = array_map('trim', explode(',', $value));
    $parts = array_values(array_filter($parts, static fn($item) => $item !== ''));
    return $parts;
  }
}

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Models\Listing;

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
        'source' => trim((string)($_GET['source'] ?? '')),
        'dataset_id' => $_GET['dataset_id'] ?? '',
      ];
      $page = max(1, (int)($_GET['page'] ?? 1));
      $perPage = 100;
      $result = Listing::search($filters, $page, $perPage);
      View::render('listings/index', [
        'title' => 'Listings',
        'filters' => $filters,
        'items' => $result['items'],
        'meta' => $result['meta'],
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
        'price_raw' => $_POST['price_raw'] ?? null,
        'price_amount' => $_POST['price_amount'] ?? null,
        'status' => $_POST['status'] ?? null,
        'payment_plan' => $_POST['payment_plan'] ?? null,
        'brochure_url' => $_POST['brochure_url'] ?? null,
        'maps_url' => $_POST['maps_url'] ?? null,
        'media_url' => $_POST['media_url'] ?? null,
        'notes' => $_POST['notes'] ?? null,
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
        'price_raw' => $_POST['price_raw'] ?? null,
        'price_amount' => $_POST['price_amount'] ?? null,
        'status' => $_POST['status'] ?? null,
        'payment_plan' => $_POST['payment_plan'] ?? null,
        'brochure_url' => $_POST['brochure_url'] ?? null,
        'maps_url' => $_POST['maps_url'] ?? null,
        'media_url' => $_POST['media_url'] ?? null,
        'notes' => $_POST['notes'] ?? null,
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
}

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Helpers\Logger;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Models\ListingDataset;
use App\Helpers\DB;

final class ListingDatasetsController extends BaseController {
  public function index(): void {
    try {
      AuthMiddleware::requireRole(['ADMIN', 'CEO']);
      $page = max(1, (int)($_GET['page'] ?? 1));
      $perPage = 10;
      $result = ListingDataset::paginate($page, $perPage);
      View::render('listings/datasets/index', [
        'title' => 'Listing Datasets',
        'items' => $result['items'],
        'meta' => $result['meta'],
      ]);
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function upload(): void {
    try {
      AuthMiddleware::requireRole(['ADMIN', 'CEO']);
      if (!$this->ensureUploadDirs()) {
        flash('danger', 'Upload directories are not writable.');
      }
      View::render('listings/datasets/upload', [
        'title' => 'Upload Listing Dataset',
      ]);
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function import(): void {
    try {
      AuthMiddleware::requireRole(['ADMIN', 'CEO']);
      CsrfMiddleware::verify();
      $datasetId = (int)($_POST['dataset_id'] ?? 0);
      if ($datasetId > 0) {
        $dataset = ListingDataset::findById($datasetId);
        if (!$dataset) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
        $dirs = $this->ensureUploadDirs();
        if (!$dirs) {
          flash('danger', 'Upload directories are not writable.');
          redirect('listings/datasets/show?id=' . $datasetId);
        }

        $stored = (string)($dataset['stored_filename'] ?? '');
        $isCsv = $this->isCsvDataset($dataset);
        $baseDir = $isCsv ? $dirs['csv_dir'] : $dirs['pdf_dir'];
        $filePath = $baseDir . '/' . basename($stored);
        if (!is_file($filePath)) {
          ListingDataset::update($datasetId, [
            'status' => 'FAILED',
            'error_message' => 'Stored dataset file is missing.',
          ]);
          flash('danger', 'Stored dataset file is missing.');
          redirect('listings/datasets/show?id=' . $datasetId);
        }

        $result = $isCsv
          ? $this->processCsvDataset($datasetId, $filePath)
          : $this->processDataset($datasetId, $filePath);
        if ($result === 'COMPLETED') {
          flash('success', 'Dataset reimport completed.');
        } else {
          flash('warning', 'Dataset reimport completed with no parsed rows.');
        }
        redirect('listings/datasets/show?id=' . $datasetId);
      }

      $csvFile = $_FILES['dataset_csv'] ?? null;
      $pdfFile = $_FILES['dataset_pdf'] ?? null;
      $hasCsv = $csvFile && ($csvFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
      $hasPdf = $pdfFile && ($pdfFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

      if (!$hasCsv && !$hasPdf) {
        flash('danger', 'Please choose a PDF or CSV file to upload.');
        redirect('listings/datasets/upload');
      }

      $dirs = $this->ensureUploadDirs();
      if (!$dirs) {
        flash('danger', 'Upload directories are not writable.');
        redirect('listings/datasets/upload');
      }

      if ($hasCsv) {
        if (($csvFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
          flash('danger', 'File upload failed.');
          redirect('listings/datasets/upload');
        }
        if (($csvFile['size'] ?? 0) > (15 * 1024 * 1024)) {
          flash('danger', 'File too large (max 15MB).');
          redirect('listings/datasets/upload');
        }
        $tmp = $csvFile['tmp_name'] ?? '';
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = strtolower($finfo->file($tmp) ?: '');
        if (!$this->isCsvMime($mime)) {
          flash('danger', 'Only CSV files are allowed.');
          redirect('listings/datasets/upload');
        }

        $randomName = bin2hex(random_bytes(16)) . '.csv';
        $dest = $dirs['csv_dir'] . '/' . $randomName;
        if (!move_uploaded_file($tmp, $dest)) {
          Logger::error('Failed to move uploaded dataset file.', ['file' => $csvFile['name'] ?? '']);
          flash('danger', 'Failed to save uploaded file.');
          redirect('listings/datasets/upload');
        }

        $hash = hash_file('sha256', $dest);
        $datasetId = ListingDataset::create([
          'uploaded_by_user_id' => current_user()['id'] ?? null,
          'original_filename' => $csvFile['name'] ?? 'dataset.csv',
          'stored_filename' => $randomName,
          'file_hash' => $hash,
          'file_size_bytes' => $csvFile['size'] ?? null,
          'mime_type' => $mime,
          'status' => 'PROCESSING',
        ]);

        $result = $this->processCsvDataset($datasetId, $dest);
        if ($result === 'COMPLETED') {
          flash('success', 'Dataset import completed.');
        } else {
          flash('warning', 'Dataset import completed with no parsed rows.');
        }
        redirect('listings/datasets/show?id=' . $datasetId);
      }

      $file = $pdfFile;
      if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        flash('danger', 'File upload failed.');
        redirect('listings/datasets/upload');
      }
      if (($file['size'] ?? 0) > (15 * 1024 * 1024)) {
        flash('danger', 'File too large (max 15MB).');
        redirect('listings/datasets/upload');
      }

      $tmp = $file['tmp_name'] ?? '';
      $finfo = new \finfo(FILEINFO_MIME_TYPE);
      $mime = $finfo->file($tmp) ?: '';
      if ($mime !== 'application/pdf') {
        flash('danger', 'Only PDF files are allowed.');
        redirect('listings/datasets/upload');
      }

      $randomName = bin2hex(random_bytes(16)) . '.pdf';
      $dest = $dirs['pdf_dir'] . '/' . $randomName;
      if (!move_uploaded_file($tmp, $dest)) {
        Logger::error('Failed to move uploaded dataset file.', ['file' => $file['name'] ?? '']);
        flash('danger', 'Failed to save uploaded file.');
        redirect('listings/datasets/upload');
      }

      $hash = hash_file('sha256', $dest);
      $datasetId = ListingDataset::create([
        'uploaded_by_user_id' => current_user()['id'] ?? null,
        'original_filename' => $file['name'] ?? 'dataset.pdf',
        'stored_filename' => $randomName,
        'file_hash' => $hash,
        'file_size_bytes' => $file['size'] ?? null,
        'mime_type' => $mime,
        'status' => 'PROCESSING',
      ]);

      $result = $this->processDataset($datasetId, $dest);
      if ($result === 'COMPLETED') {
        flash('success', 'Dataset import completed.');
      } else {
        flash('warning', 'Dataset import completed with no parsed rows.');
      }
      redirect('listings/datasets/show?id=' . $datasetId);
    } catch (\Throwable $e) {
      Logger::error('Dataset upload failed.', ['error' => $e->getMessage()]);
      $this->handleException($e);
    }
  }

  public function show(): void {
    try {
      AuthMiddleware::requireRole(['ADMIN', 'CEO']);
      $id = (int)($_GET['id'] ?? 0);
      if ($id <= 0) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      $dataset = ListingDataset::findById($id);
      if (!$dataset) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      $preview = '';
      if (!empty($dataset['extracted_text_path'])) {
        $path = __DIR__ . '/../../' . ltrim((string)$dataset['extracted_text_path'], '/');
        if (is_file($path)) {
          $preview = (string)file_get_contents($path);
          $preview = substr($preview, 0, 1500);
        }
      }
      $pdo = DB::conn();
      $st = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE dataset_id=:id");
      $st->execute([':id' => $id]);
      $listingsCount = (int)$st->fetchColumn();
      View::render('listings/datasets/show', [
        'title' => 'Dataset Details',
        'dataset' => $dataset,
        'preview' => $preview,
        'listings_count' => $listingsCount,
      ]);
    } catch (\Throwable $e) {
      $this->handleException($e);
    }
  }

  public function download(): void {
    try {
      AuthMiddleware::requireRole(['ADMIN', 'CEO']);
      $id = (int)($_GET['id'] ?? 0);
      if ($id <= 0) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }
      $dataset = ListingDataset::findById($id);
      if (!$dataset) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }

      $stored = (string)($dataset['stored_filename'] ?? '');
      if ($stored === '') {
        http_response_code(404);
        require __DIR__ . '/../Views/errors/404.php';
        return;
      }

      $isCsv = $this->isCsvDataset($dataset);
      $baseDir = $isCsv ? 'uploads/listings/csv' : 'uploads/listings/pdfs';
      $path = __DIR__ . '/../../' . $baseDir . '/' . basename($stored);
      if (!is_file($path)) {
        http_response_code(404);
        require __DIR__ . '/../Views/errors/404.php';
        return;
      }

      $downloadName = basename((string)($dataset['original_filename'] ?? 'dataset.pdf'));
      header('Content-Type: ' . ($isCsv ? 'text/csv' : 'application/pdf'));
      header('Content-Disposition: attachment; filename="' . $downloadName . '"');
      header('Content-Length: ' . (string)filesize($path));
      header('X-Content-Type-Options: nosniff');
      readfile($path);
      exit;
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
      $dataset = ListingDataset::findById($id);
      if (!$dataset) { http_response_code(404); require __DIR__ . '/../Views/errors/404.php'; return; }

      $pdo = DB::conn();
      $pdo->beginTransaction();
      try {
        $st = $pdo->prepare("DELETE FROM listings WHERE dataset_id=:id");
        $st->execute([':id' => $id]);
        ListingDataset::delete($id);
        $pdo->commit();
      } catch (\Throwable $txError) {
        $pdo->rollBack();
        throw $txError;
      }

      $stored = (string)($dataset['stored_filename'] ?? '');
      if ($stored !== '') {
        $dirs = $this->ensureUploadDirs();
        if ($dirs) {
          $baseDir = $this->isCsvDataset($dataset) ? $dirs['csv_dir'] : $dirs['pdf_dir'];
          $path = $baseDir . '/' . basename($stored);
          if (is_file($path)) {
            @unlink($path);
          }
        }
      }
      $extractRel = (string)($dataset['extracted_text_path'] ?? '');
      if ($extractRel !== '') {
        $extractPath = __DIR__ . '/../../' . ltrim($extractRel, '/');
        if (is_file($extractPath)) {
          @unlink($extractPath);
        }
      }

      flash('success', 'Dataset and related listings deleted.');
      redirect('listings/datasets');
    } catch (\Throwable $e) {
      Logger::error('Dataset delete failed.', ['error' => $e->getMessage()]);
      $this->handleException($e);
    }
  }

  private function isPdfToTextAvailable(): bool {
    $output = [];
    $code = 0;
    @exec('pdftotext -h 2>&1', $output, $code);
    $joined = strtolower(implode(' ', $output));
    if (strpos($joined, 'not recognized') !== false || strpos($joined, 'not found') !== false) {
      return false;
    }
    return $code === 0 || $code === 1;
  }

  private function extractPdfText(string $pdfPath, string $outputPath): bool {
    $cmd = 'pdftotext -layout ' . escapeshellarg($pdfPath) . ' ' . escapeshellarg($outputPath) . ' 2>&1';
    $output = [];
    $code = 0;
    @exec($cmd, $output, $code);
    if ($code !== 0) {
      Logger::error('pdftotext command failed.', ['output' => implode(' ', $output)]);
      return false;
    }
    return true;
  }

  private function parsePdfText(string $text): array {
    $records = [];
    $lines = preg_split('/\R/', $text);
    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '') continue;
      if (preg_match('/\s{2,}/', $line)) {
        $cols = preg_split('/\s{2,}/', $line);
        $parsed = $this->mapColumns($cols);
        $records[] = [
          'row_text' => $line,
          'parsed' => $parsed,
          'parser' => 'table-split',
          'confidence' => $this->confidenceForParsed($parsed),
        ];
      }
    }

    if ($records) return $records;

    $blocks = [];
    $current = [];
    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '') {
        if ($current) {
          $blocks[] = implode(' ', $current);
          $current = [];
        }
        continue;
      }
      $current[] = $line;
      if (count($current) >= 3) {
        $blocks[] = implode(' ', $current);
        $current = [];
      }
    }
    if ($current) $blocks[] = implode(' ', $current);

    foreach ($blocks as $block) {
      $parsed = $this->parseBlock($block);
      $records[] = [
        'row_text' => $block,
        'parsed' => $parsed,
        'parser' => 'line-block',
        'confidence' => $this->confidenceForParsed($parsed),
      ];
    }

    return $records;
  }

  private function mapColumns(array $cols): array {
    $cols = array_values(array_map('trim', $cols));
    $sizeRaw = $cols[7] ?? null;
    $priceRaw = $cols[8] ?? null;
    return [
      'project_name' => $cols[0] ?? '',
      'area' => $cols[1] ?? '',
      'developer' => $cols[2] ?? null,
      'unit_ref' => $cols[3] ?? null,
      'property_type' => $cols[4] ?? null,
      'beds_raw' => $cols[5] ?? null,
      'beds' => $this->parseInt($cols[5] ?? null),
      'baths_raw' => $cols[6] ?? null,
      'baths' => $this->parseInt($cols[6] ?? null),
      'size_raw' => $sizeRaw,
      'size_sqft' => $this->parseSize($sizeRaw),
      'price_raw' => $priceRaw,
      'price_amount' => $this->parseAmount($priceRaw),
      'status' => $cols[9] ?? null,
    ];
  }

  private function parseBlock(string $block): array {
    $parts = preg_split('/\s*\|\s*|\s+-\s+|,/', $block);
    $parts = array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));
    $projectName = $parts[0] ?? '';
    $area = $parts[1] ?? 'Unknown';
    $sizeRaw = $this->extractSizeRaw($block);
    $priceRaw = $this->extractPriceRaw($block);
    return [
      'project_name' => $projectName,
      'area' => $area,
      'size_raw' => $sizeRaw,
      'size_sqft' => $this->parseSize($sizeRaw),
      'price_raw' => $priceRaw,
      'price_amount' => $this->parseAmount($priceRaw),
      'notes' => $block,
    ];
  }

  private function confidenceForParsed(array $parsed): float {
    $score = 0.0;
    if (!empty($parsed['project_name'])) $score += 0.4;
    if (!empty($parsed['area'])) $score += 0.3;
    if (!empty($parsed['price_amount']) || !empty($parsed['price_raw'])) $score += 0.15;
    if (!empty($parsed['size_sqft']) || !empty($parsed['size_raw'])) $score += 0.15;
    return min(1.0, $score);
  }

  private function parseInt($value): ?int {
    if ($value === null) return null;
    $value = trim((string)$value);
    if ($value === '' || !is_numeric($value)) return null;
    return (int)$value;
  }

  private function parseAmount(?string $raw): ?string {
    if (!$raw) return null;
    if (preg_match('/\d[\d,]*(\.\d+)?/', $raw, $m)) {
      return str_replace(',', '', $m[0]);
    }
    return null;
  }

  private function parseSize(?string $raw): ?string {
    if (!$raw) return null;
    if (preg_match('/\d[\d,]*(\.\d+)?/', $raw, $m)) {
      return str_replace(',', '', $m[0]);
    }
    return null;
  }

  private function extractSizeRaw(string $text): ?string {
    if (preg_match('/\d[\d,]*(\.\d+)?\s*(sqft|sq\.?ft|ft2)/i', $text, $m)) {
      return $m[0];
    }
    return null;
  }

  private function extractPriceRaw(string $text): ?string {
    if (preg_match('/(aed|usd|eur)\s*\d[\d,]*(\.\d+)?/i', $text, $m)) {
      return $m[0];
    }
    if (preg_match('/\d[\d,]*(\.\d+)?/', $text, $m)) {
      return $m[0];
    }
    return null;
  }

  private function ensureUploadDirs(): ?array {
    $pdfDir = __DIR__ . '/../../uploads/listings/pdfs';
    $extractDir = __DIR__ . '/../../uploads/listings/extracted_text';
    $csvDir = __DIR__ . '/../../uploads/listings/csv';
    if (!is_dir($pdfDir) && !mkdir($pdfDir, 0755, true) && !is_dir($pdfDir)) {
      Logger::error('Failed to create listings upload directory.', ['dir' => $pdfDir]);
      return null;
    }
    if (!is_dir($extractDir) && !mkdir($extractDir, 0755, true) && !is_dir($extractDir)) {
      Logger::error('Failed to create extracted text directory.', ['dir' => $extractDir]);
      return null;
    }
    if (!is_dir($csvDir) && !mkdir($csvDir, 0755, true) && !is_dir($csvDir)) {
      Logger::error('Failed to create listings CSV directory.', ['dir' => $csvDir]);
      return null;
    }
    if (!is_writable($pdfDir) || !is_writable($extractDir)) {
      Logger::error('Listings upload directories not writable.', ['pdf' => $pdfDir, 'extract' => $extractDir]);
      return null;
    }
    if (!is_writable($csvDir)) {
      Logger::error('Listings CSV directory not writable.', ['csv' => $csvDir]);
      return null;
    }
    return ['pdf_dir' => $pdfDir, 'extract_dir' => $extractDir, 'csv_dir' => $csvDir];
  }

  private function processDataset(int $datasetId, string $pdfPath): string {
    if (!$this->isPdfToTextAvailable()) {
      Logger::error('pdftotext not available for dataset import.', ['dataset_id' => $datasetId]);
      ListingDataset::update($datasetId, [
        'status' => 'FAILED',
        'error_message' => 'pdftotext is not available on the server.',
      ]);
      return 'FAILED';
    }

    $extractRel = 'uploads/listings/extracted_text/' . $datasetId . '.txt';
    $extractAbs = __DIR__ . '/../../' . $extractRel;
    $extractOk = $this->extractPdfText($pdfPath, $extractAbs);
    if (!$extractOk || !is_file($extractAbs)) {
      Logger::error('Failed to extract text from PDF.', ['dataset_id' => $datasetId]);
      ListingDataset::update($datasetId, [
        'status' => 'FAILED',
        'error_message' => 'PDF extraction failed.',
      ]);
      return 'FAILED';
    }

    $text = file_get_contents($extractAbs) ?: '';
    $records = $this->parsePdfText($text);

    $pdo = DB::conn();
    $pdo->beginTransaction();
    $finalStatus = 'FAILED';
    try {
      ListingDataset::update($datasetId, [
        'extracted_text_path' => $extractRel,
        'status' => 'PROCESSING',
        'parsed_count' => 0,
        'failed_count' => 0,
        'error_message' => null,
      ]);

      $parsedCount = 0;
      $failedCount = 0;

      foreach ($records as $record) {
        try {
          $rowText = $record['row_text'] ?? '';
          $parsed = $record['parsed'] ?? [];
          $confidence = $record['confidence'] ?? 0;
          $parser = $record['parser'] ?? 'unknown';

          $projectName = trim((string)($parsed['project_name'] ?? ''));
          $area = trim((string)($parsed['area'] ?? ''));
          if ($projectName === '' || $area === '') {
            $failedCount++;
            continue;
          }

          $bedsValue = $parsed['beds'] ?? null;
          if (is_string($bedsValue) && preg_match('/\d+/', $bedsValue, $m)) {
            $bedsValue = (int)$m[0];
          }
          if ($bedsValue !== null && !is_int($bedsValue)) {
            $bedsValue = null;
          }

          \App\Models\Listing::create([
            'project_name' => $projectName,
            'area' => $area,
            'developer' => $parsed['developer'] ?? null,
            'unit_ref' => $parsed['unit_ref'] ?? null,
            'property_type' => $parsed['property_type'] ?? null,
            'beds_raw' => $parsed['beds_raw'] ?? null,
            'beds' => $bedsValue,
            'baths_raw' => $parsed['baths_raw'] ?? null,
            'baths' => $parsed['baths'] ?? null,
            'size_raw' => $parsed['size_raw'] ?? null,
            'size_sqft' => $parsed['size_sqft'] ?? null,
            'price_raw' => $parsed['price_raw'] ?? null,
            'price_amount' => $parsed['price_amount'] ?? null,
            'status' => $parsed['status'] ?? null,
            'payment_plan' => $parsed['payment_plan'] ?? null,
            'brochure_url' => $parsed['brochure_url'] ?? null,
            'maps_url' => $parsed['maps_url'] ?? null,
            'media_url' => $parsed['media_url'] ?? null,
            'notes' => $parsed['notes'] ?? null,
            'source' => 'PDF',
            'dataset_id' => $datasetId,
            'raw_data' => [
              'row_text' => $rowText,
              'parsed' => $parsed,
              'parser' => $parser,
              'confidence' => $confidence,
            ],
            'created_by_user_id' => current_user()['id'] ?? null,
          ]);
          $parsedCount++;
        } catch (\Throwable $rowError) {
          $failedCount++;
          Logger::error('Failed to insert listing from dataset.', [
            'dataset_id' => $datasetId,
            'error' => $rowError->getMessage(),
          ]);
        }
      }

      $finalStatus = $parsedCount > 0 ? 'COMPLETED' : 'FAILED';
      ListingDataset::update($datasetId, [
        'parsed_count' => $parsedCount,
        'failed_count' => $failedCount,
        'status' => $finalStatus,
        'error_message' => $parsedCount > 0 ? null : 'No rows parsed from PDF.',
      ]);

      $pdo->commit();
    } catch (\Throwable $txError) {
      $pdo->rollBack();
      Logger::error('Dataset import transaction failed.', ['dataset_id' => $datasetId, 'error' => $txError->getMessage()]);
      ListingDataset::update($datasetId, [
        'status' => 'FAILED',
        'error_message' => 'Import transaction failed.',
      ]);
      $finalStatus = 'FAILED';
    }

    return $finalStatus;
  }

  private function isCsvMime(string $mime): bool {
    $mime = strtolower(trim($mime));
    return in_array($mime, ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'], true);
  }

  private function isCsvDataset(array $dataset): bool {
    $mime = (string)($dataset['mime_type'] ?? '');
    $stored = strtolower((string)($dataset['stored_filename'] ?? ''));
    if ($this->isCsvMime($mime)) return true;
    return $stored !== '' && str_ends_with($stored, '.csv');
  }

  private function normalizeCsvHeader(string $header): string {
    $header = preg_replace('/^\xEF\xBB\xBF/', '', $header ?? '');
    $header = strtolower(trim($header));
    $header = str_replace([' ', '-'], '_', $header);
    return $header;
  }

  private function mapCsvRow(array $headerIndex, array $row): array {
    $get = function (string $key) use ($headerIndex, $row): ?string {
      if (!array_key_exists($key, $headerIndex)) return null;
      $idx = $headerIndex[$key];
      if (!array_key_exists($idx, $row)) return null;
      $value = $row[$idx];
      $value = is_string($value) ? trim($value) : $value;
      return $value === '' ? null : (string)$value;
    };

    $getAny = function (array $keys) use ($get): ?string {
      foreach ($keys as $key) {
        $value = $get($key);
        if ($value !== null) return $value;
      }
      return null;
    };

    $area = $getAny(['area', 'location']);
    $notes = $get('notes');
    $location = $get('location');
    if ($notes === null && $location !== null) {
      $notes = 'Location: ' . $location;
    }

    return [
      'project_name' => $getAny(['project_name', 'project']) ?? '',
      'area' => $area ?? '',
      'developer' => $get('developer'),
      'unit_ref' => $getAny(['unit_ref', 'unit']),
      'property_type' => $getAny(['property_type', 'type']),
      'beds_raw' => $getAny(['beds_raw', 'beds', 'bedrooms']),
      'beds' => $getAny(['beds', 'bedrooms']),
      'baths_raw' => $getAny(['baths_raw', 'baths']),
      'baths' => $get('baths'),
      'size_raw' => $getAny(['size_raw', 'size']),
      'size_sqft' => $getAny(['size_sqft', 'size']),
      'price_raw' => $getAny(['price_raw', 'price']),
      'price_amount' => $getAny(['price_amount', 'price']),
      'status' => $get('status'),
      'payment_plan' => $getAny(['payment_plan', 'payment']),
      'brochure_url' => $getAny(['brochure_url', 'brochure', 'listing_url']),
      'maps_url' => $getAny(['maps_url', 'maps']),
      'media_url' => $getAny(['media_url', 'media']),
      'notes' => $notes,
    ];
  }

  private function processCsvDataset(int $datasetId, string $csvPath): string {
    if (!is_file($csvPath)) {
      ListingDataset::update($datasetId, [
        'status' => 'FAILED',
        'error_message' => 'CSV file is missing.',
      ]);
      return 'FAILED';
    }

    $handle = fopen($csvPath, 'rb');
    if (!$handle) {
      ListingDataset::update($datasetId, [
        'status' => 'FAILED',
        'error_message' => 'Failed to open CSV file.',
      ]);
      return 'FAILED';
    }

    $firstLine = fgets($handle);
    if ($firstLine === false) {
      fclose($handle);
      ListingDataset::update($datasetId, [
        'status' => 'FAILED',
        'error_message' => 'CSV header row is missing.',
      ]);
      return 'FAILED';
    }

    $delimiter = substr_count($firstLine, "\t") > substr_count($firstLine, ',') ? "\t" : ',';
    $headers = str_getcsv($firstLine, $delimiter);
    if (!$headers || !is_array($headers)) {
      fclose($handle);
      ListingDataset::update($datasetId, [
        'status' => 'FAILED',
        'error_message' => 'CSV header row is missing.',
      ]);
      return 'FAILED';
    }

    $headerIndex = [];
    foreach ($headers as $idx => $header) {
      $normalized = $this->normalizeCsvHeader((string)$header);
      if ($normalized !== '') {
        $headerIndex[$normalized] = $idx;
      }
    }
    $hasProject = array_key_exists('project_name', $headerIndex) || array_key_exists('project', $headerIndex);
    $hasArea = array_key_exists('area', $headerIndex) || array_key_exists('location', $headerIndex);
    if (!$hasProject || !$hasArea) {
      fclose($handle);
      ListingDataset::update($datasetId, [
        'status' => 'FAILED',
        'error_message' => 'CSV headers must include Project and Area (or Location).',
      ]);
      return 'FAILED';
    }

    $pdo = DB::conn();
    $pdo->beginTransaction();
    $finalStatus = 'FAILED';
    try {
      ListingDataset::update($datasetId, [
        'extracted_text_path' => null,
        'status' => 'PROCESSING',
        'parsed_count' => 0,
        'failed_count' => 0,
        'error_message' => null,
      ]);

      $parsedCount = 0;
      $failedCount = 0;

      while (($line = fgets($handle)) !== false) {
        $row = str_getcsv($line, $delimiter);
        if (!$row || (count($row) === 1 && trim((string)$row[0]) === '')) {
          continue;
        }
        try {
          $parsed = $this->mapCsvRow($headerIndex, $row);
          $projectName = trim((string)($parsed['project_name'] ?? ''));
          $area = trim((string)($parsed['area'] ?? ''));
          if ($projectName === '' || $area === '') {
            $failedCount++;
            continue;
          }

          \App\Models\Listing::create([
            'project_name' => $projectName,
            'area' => $area,
            'developer' => $parsed['developer'] ?? null,
            'unit_ref' => $parsed['unit_ref'] ?? null,
            'property_type' => $parsed['property_type'] ?? null,
            'beds_raw' => $parsed['beds_raw'] ?? null,
            'beds' => $parsed['beds'] ?? null,
            'baths_raw' => $parsed['baths_raw'] ?? null,
            'baths' => $parsed['baths'] ?? null,
            'size_raw' => $parsed['size_raw'] ?? null,
            'size_sqft' => $parsed['size_sqft'] ?? null,
            'price_raw' => $parsed['price_raw'] ?? null,
            'price_amount' => $parsed['price_amount'] ?? null,
            'status' => $parsed['status'] ?? null,
            'payment_plan' => $parsed['payment_plan'] ?? null,
            'brochure_url' => $parsed['brochure_url'] ?? null,
            'maps_url' => $parsed['maps_url'] ?? null,
            'media_url' => $parsed['media_url'] ?? null,
            'notes' => $parsed['notes'] ?? null,
            'source' => 'MANUAL',
            'dataset_id' => $datasetId,
            'raw_data' => [
              'source' => 'CSV',
              'row' => $row,
            ],
            'created_by_user_id' => current_user()['id'] ?? null,
          ]);
          $parsedCount++;
        } catch (\Throwable $rowError) {
          $failedCount++;
          Logger::error('Failed to insert listing from CSV dataset.', [
            'dataset_id' => $datasetId,
            'error' => $rowError->getMessage(),
          ]);
        }
      }

      $finalStatus = $parsedCount > 0 ? 'COMPLETED' : 'FAILED';
      ListingDataset::update($datasetId, [
        'parsed_count' => $parsedCount,
        'failed_count' => $failedCount,
        'status' => $finalStatus,
        'error_message' => $parsedCount > 0 ? null : 'No rows parsed from CSV.',
      ]);

      $pdo->commit();
    } catch (\Throwable $txError) {
      $pdo->rollBack();
      Logger::error('CSV dataset import transaction failed.', ['dataset_id' => $datasetId, 'error' => $txError->getMessage()]);
      ListingDataset::update($datasetId, [
        'status' => 'FAILED',
        'error_message' => 'Import transaction failed.',
      ]);
      $finalStatus = 'FAILED';
    } finally {
      fclose($handle);
    }

    return $finalStatus;
  }
}

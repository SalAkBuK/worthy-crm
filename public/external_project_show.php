<?php
declare(strict_types=1);

require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../includes/external_projects_api.php';
require_once __DIR__ . '/../includes/external_projects_normalize.php';

require_role(['ADMIN', 'CEO', 'AGENT']);

$projectId = (int)($_GET['id'] ?? 0);
$projectTitle = trim((string)($_GET['title'] ?? ''));
$projectDistrict = trim((string)($_GET['district'] ?? ''));
$projectPrice = trim((string)($_GET['price'] ?? ''));
$projectHandover = trim((string)($_GET['handover'] ?? ''));
$projectImage = trim((string)($_GET['image'] ?? ''));

$details = [];
$detailLogPath = '';
if ($projectId > 0) {
  $detailResponse = fetchExternalProjectDetail($projectId);
  if (!empty($detailResponse['data']) && is_array($detailResponse['data'])) {
    $details = $detailResponse['data'];
  }
  $logDetail = (string)($_GET['log'] ?? '');
  if ($logDetail === '1') {
    $logDir = __DIR__ . '/../storage/logs';
    if (!is_dir($logDir)) {
      @mkdir($logDir, 0775, true);
    }
    $detailLogPath = $logDir . '/external_project_detail_' . $projectId . '_' . date('Ymd_His') . '.json';
    $snapshot = [
      'project_id' => $projectId,
      'fetched_at' => date('c'),
      'status' => $detailResponse['status'] ?? null,
      'warning' => $detailResponse['warning'] ?? null,
      'meta' => $detailResponse['meta'] ?? null,
      'data' => $detailResponse['data'] ?? null,
    ];
    @file_put_contents($detailLogPath, json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
  }
}
if (!$details) {
  $detailsPath = __DIR__ . '/../amra_details.normalized.json';
  $detailsRaw = is_file($detailsPath) ? file_get_contents($detailsPath) : false;
  if (is_string($detailsRaw) && $detailsRaw !== '') {
    $decoded = json_decode($detailsRaw, true);
    if (is_array($decoded)) {
      $details = $decoded;
    }
  }
}

$project = is_array($details['project'] ?? null) ? $details['project'] : (is_array($details) ? $details : []);
$descriptions = is_array($details['descriptions'] ?? null) ? $details['descriptions'] : [];
if (!$descriptions) {
  $generalFacts = $details['general_facts'] ?? null;
  $locationBenefits = $details['location_benefits'] ?? null;
  if (is_string($generalFacts) || is_string($locationBenefits)) {
    $descriptions = [
      'general_facts' => is_string($generalFacts) ? $generalFacts : '',
      'location_benefits' => is_string($locationBenefits) ? $locationBenefits : '',
    ];
  }
}
if (!$descriptions && !empty($project['description']) && is_string($project['description'])) {
  $descriptions = [
    'general_facts' => $project['description'],
    'location_benefits' => '',
  ];
}
$descriptionHtml = '';
if (!empty($project['description']) && is_string($project['description'])) {
  $descriptionHtml = strip_tags($project['description'], '<p><br><strong><b><em><ul><ol><li>');
}
$projectName = $projectTitle !== '' ? $projectTitle : (string)($project['name'] ?? 'Project Details');
$projectLocation = $projectDistrict !== '' ? $projectDistrict : (string)($project['project_location'] ?? ($project['location_text'] ?? ($project['area'] ?? ($project['district'] ?? ''))));
$projectStatus = (string)($project['status'] ?? ($project['project_status'] ?? 'Off-Plan'));
$handoverDate = $projectHandover !== '' ? $projectHandover : (string)($project['handover_date'] ?? ($project['expected_completion_date'] ?? ''));
$isCompleted = compute_project_is_completed($project);
$startingPrice = '';
if ($projectPrice !== '') {
  $startingPrice = $projectPrice;
} elseif (!empty($project['starting_price']) && is_array($project['starting_price'])) {
  $amount = $project['starting_price']['amount'] ?? null;
  $currency = (string)($project['starting_price']['currency'] ?? '');
  if (is_numeric($amount)) {
    $startingPrice = trim($currency . ' ' . number_format((float)$amount));
  }
}
if ($startingPrice === '' && !empty($project['price'])) {
  $startingPrice = (string)$project['price'];
}
if ($startingPrice === '' && (!empty($project['price_start']) || !empty($project['up_front_price']))) {
  $raw = $project['price_start'] ?? $project['up_front_price'];
  $currency = (string)($project['currency'] ?? '');
  if (is_numeric($raw)) {
    $startingPrice = trim($currency . ' ' . number_format((float)$raw, 0, '.', ','));
  } else {
    $startingPrice = trim($currency . ' ' . (string)$raw);
  }
}

$title = $projectName;
$page_title = $title;
$page_subtitle = $projectLocation !== '' ? $projectLocation : 'External Project';
$apiQuery = http_build_query([
  'id' => $projectId > 0 ? $projectId : null,
]);
$apiUrl = url('api/project.php') . ($apiQuery !== '' ? ('?' . $apiQuery) : '');

$developerName = (string)($project['developer'] ?? ($details['developer']['name'] ?? 'Developer'));
$developerName = (string)($project['developer_name'] ?? $developerName);
$developerAddress = '';
$developerEmail = '';
$developerPhone = '';
$developerLogo = '';
$developerWebsite = '';
$developerDescriptionHtml = '';
$developerWorkingTime = [];
if (!empty($details['developer_contacts']) && is_array($details['developer_contacts'])) {
  $firstContact = $details['developer_contacts'][0] ?? [];
  if (is_array($firstContact)) {
    $developerName = (string)($firstContact['name'] ?? $developerName);
    $developerAddress = (string)($firstContact['address'] ?? '');
  }
}
if ($developerAddress === '' && !empty($details['developer']['address'])) {
  $developerAddress = (string)$details['developer']['address'];
}
if ($developerAddress === '' && !empty($project['developer_address'])) {
  $developerAddress = (string)$project['developer_address'];
}
if (!empty($project['developer_email'])) {
  $developerEmail = (string)$project['developer_email'];
} elseif (!empty($details['developer']['email'])) {
  $developerEmail = (string)$details['developer']['email'];
}
if (!empty($project['developer_phone'])) {
  $developerPhone = (string)$project['developer_phone'];
} elseif (!empty($details['developer']['phone'])) {
  $developerPhone = (string)$details['developer']['phone'];
}
if (!empty($project['developer_image'])) {
  $developerLogo = (string)$project['developer_image'];
} elseif (!empty($details['developer']['image'])) {
  $developerLogo = (string)$details['developer']['image'];
}
if (!empty($project['developer_website'])) {
  $developerWebsite = (string)$project['developer_website'];
} elseif (!empty($details['developer']['website'])) {
  $developerWebsite = (string)$details['developer']['website'];
}
if (!empty($project['developer_description']) && is_string($project['developer_description'])) {
  $developerDescriptionHtml = strip_tags($project['developer_description'], '<p><br><strong><b><em><ul><ol><li>');
} elseif (!empty($details['developer']['description']) && is_string($details['developer']['description'])) {
  $developerDescriptionHtml = strip_tags($details['developer']['description'], '<p><br><strong><b><em><ul><ol><li>');
}
if (!empty($project['developers_data']) && is_array($project['developers_data'])) {
  $primaryDeveloper = $project['developers_data'][0] ?? null;
  if (is_array($primaryDeveloper)) {
    $developerName = (string)($primaryDeveloper['name'] ?? $developerName);
    if (!empty($primaryDeveloper['email'])) $developerEmail = (string)$primaryDeveloper['email'];
    if (!empty($primaryDeveloper['image'])) $developerLogo = (string)$primaryDeveloper['image'];
    if (!empty($primaryDeveloper['website'])) $developerWebsite = (string)$primaryDeveloper['website'];
    if (!empty($primaryDeveloper['address'])) $developerAddress = (string)$primaryDeveloper['address'];
    if (!empty($primaryDeveloper['description']) && is_string($primaryDeveloper['description'])) {
      $developerDescriptionHtml = strip_tags($primaryDeveloper['description'], '<p><br><strong><b><em><ul><ol><li>');
    }
    if (!empty($primaryDeveloper['working_time']) && is_array($primaryDeveloper['working_time'])) {
      $developerWorkingTime = $primaryDeveloper['working_time'];
    }
  }
}

$salesExecutives = [];
$rawSales = $project['sales_executives'] ?? ($details['sales_executives'] ?? null);
if (is_array($rawSales)) {
  foreach ($rawSales as $entry) {
    if (is_array($entry)) {
      $salesExecutives[] = $entry;
    }
  }
}

$facilityBadges = [];
if (!empty($details['amenities']) && is_array($details['amenities'])) {
  foreach ($details['amenities'] as $amenity) {
    if (is_string($amenity) && $amenity !== '') {
      $facilityBadges[] = $amenity;
    }
  }
}
if (!empty($project['amenities_and_features']['features_names']) && is_array($project['amenities_and_features']['features_names'])) {
  foreach ($project['amenities_and_features']['features_names'] as $amenity) {
    if (is_string($amenity) && $amenity !== '') {
      $facilityBadges[] = $amenity;
    }
  }
}
if (!$facilityBadges) {
  $facilityBadges = [
    'Infinity Pool',
    'Spa',
    'Gym',
    'Yoga Studio',
    'Co-working',
    'Private Beach',
  ];
}

if ($projectImage === '') {
  if (!empty($project['banner_image']) && is_string($project['banner_image'])) {
    $projectImage = $project['banner_image'];
  } elseif (!empty($project['image']) && is_string($project['image'])) {
    $projectImage = $project['image'];
  } elseif (!empty($project['images']) && is_array($project['images'])) {
    $firstImage = $project['images'][0] ?? null;
    if (is_string($firstImage)) {
      $projectImage = $firstImage;
    } elseif (is_array($firstImage) && !empty($firstImage['url'])) {
      $projectImage = (string)$firstImage['url'];
    }
  }
}

$carouselImages = [];
$imagesField = $project['images'] ?? null;
if (is_array($imagesField)) {
  if (!empty($imagesField['feature'])) {
    $carouselImages[] = (string)$imagesField['feature'];
  }
  if (!empty($imagesField['other']) && is_array($imagesField['other'])) {
    foreach ($imagesField['other'] as $img) {
      if (is_string($img) && $img !== '') {
        $carouselImages[] = $img;
      }
    }
  }
}
if (!empty($project['all_images']) && is_array($project['all_images'])) {
  foreach ($project['all_images'] as $img) {
    if (is_string($img) && $img !== '') {
      $carouselImages[] = $img;
    }
  }
}
if ($projectImage !== '') {
  array_unshift($carouselImages, $projectImage);
}
$carouselImages = array_values(array_unique(array_filter($carouselImages, function ($img): bool {
  return is_string($img) && $img !== '';
})));

$mapEmbedUrl = '';
$mapImageUrl = '';
$mapLinkUrl = '';
if (!empty($project['map_url']) && is_string($project['map_url'])) {
  $mapEmbedUrl = $project['map_url'];
  $mapLinkUrl = $project['map_url'];
} elseif (!empty($project['latlong']) && is_string($project['latlong'])) {
  $mapEmbedUrl = 'https://www.google.com/maps?q=' . urlencode($project['latlong']) . '&z=15&output=embed';
  $mapLinkUrl = 'https://www.google.com/maps?q=' . urlencode($project['latlong']) . '&z=15';
}
if (!empty($project['map_img']) && is_string($project['map_img'])) {
  $mapImageUrl = $project['map_img'];
}

$facilityItems = [];
if (!empty($project['facilities']) && is_array($project['facilities'])) {
  foreach ($project['facilities'] as $facility) {
    if (!is_array($facility)) continue;
    $name = (string)($facility['name'] ?? '');
    $image = (string)($facility['image'] ?? '');
    if ($name === '' && $image === '') continue;
    $facilityItems[] = ['name' => $name, 'image' => $image];
  }
}

$attachments = [];
if (!empty($project['attachments']) && is_array($project['attachments'])) {
  foreach ($project['attachments'] as $attachment) {
    if (!is_array($attachment)) continue;
    $title = trim((string)($attachment['attachment_title'] ?? $attachment['title'] ?? 'Attachment'));
    $url = trim((string)($attachment['attachment_url'] ?? $attachment['url'] ?? ''));
    $type = trim((string)($attachment['file_type'] ?? ''));
    if ($url === '') continue;
    $attachments[] = [
      'title' => $title !== '' ? $title : 'Attachment',
      'url' => $url,
      'type' => $type,
    ];
  }
}

$parkingItems = [];
$parkingTitle = '';
if (!empty($project['parking_json']) && is_array($project['parking_json'])) {
  $parkingTitle = (string)($project['parking_json'][0]['title'] ?? '');
  $parkingItems = $project['parking_json'][0]['data'] ?? [];
} elseif (!empty($project['parkings']) && is_array($project['parkings'])) {
  $parkingItems = $project['parkings'];
}

ob_start();
?>
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <div>
        <h4 class="mb-1"><?= e($title) ?></h4>
        <?php if ($projectLocation !== ''): ?>
          <div class="text-muted"><?= e($projectLocation) ?></div>
        <?php endif; ?>
      </div>
      <a class="btn btn-outline-light" href="<?= e(url('external_projects.php')) ?>">Back</a>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-3 col-lg-4">
    <div class="card">
      <div class="card-header bg-light-subtle">
        <h4 class="card-title">Developer Details</h4>
      </div>
      <div class="card-body">
        <div class="text-center">
          <div class="avatar-xl rounded-circle border border-2 border-light mx-auto bg-light d-flex align-items-center justify-content-center overflow-hidden">
            <?php if ($developerLogo !== ''): ?>
              <?php
              $payload = rtrim(strtr(base64_encode($developerLogo), '+/', '-_'), '=');
              $signature = hash_hmac('sha256', $payload, app_key());
              $imageToken = $payload . '.' . $signature;
              ?>
              <img src="<?= e(url('image-proxy.php?id=' . $imageToken)) ?>"
                   data-fallback="<?= e($developerLogo) ?>"
                   onerror="if (this.dataset.fallback) { this.src = this.dataset.fallback; this.removeAttribute('data-fallback'); }"
                   alt="<?= e($developerName) ?>"
                   class="img-fluid rounded-circle"
                   style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
              <iconify-icon icon="solar:user-rounded-bold-duotone" class="fs-32 text-muted"></iconify-icon>
            <?php endif; ?>
          </div>
          <div class="mt-2">
            <span class="fw-medium text-dark fs-16"><?= e($developerName) ?></span>
            <p class="mb-0 text-muted"><?= e($projectStatus !== '' ? $projectStatus : 'Off-Plan') ?></p>
          </div>
          <?php if ($developerAddress !== ''): ?>
            <p class="mt-2 text-muted mb-0"><?= e($developerAddress) ?></p>
          <?php endif; ?>
          <?php if ($developerEmail !== ''): ?>
            <p class="mt-2 mb-0">
              <a href="mailto:<?= e($developerEmail) ?>" class="text-decoration-none"><?= e($developerEmail) ?></a>
            </p>
          <?php endif; ?>
          <?php if ($developerWebsite !== ''): ?>
            <p class="mt-1 mb-0">
              <a href="<?= e($developerWebsite) ?>" target="_blank" rel="noopener" class="text-decoration-none"><?= e($developerWebsite) ?></a>
            </p>
          <?php endif; ?>
          <?php if ($developerPhone !== ''): ?>
            <p class="mt-1 mb-0">
              <a href="tel:<?= e($developerPhone) ?>" class="text-decoration-none"><?= e($developerPhone) ?></a>
            </p>
          <?php endif; ?>
          <?php if ($developerDescriptionHtml !== ''): ?>
            <div class="text-muted mt-2"><?= $developerDescriptionHtml ?></div>
          <?php endif; ?>
          <?php if ($developerWorkingTime): ?>
            <div class="text-muted mt-2">
              <?php foreach ($developerWorkingTime as $day => $hours): ?>
                <div><?= e((string)$day) ?>: <?= e((string)$hours) ?></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php if ($salesExecutives): ?>
      <div class="card mt-3">
        <div class="card-header bg-light-subtle">
          <h4 class="card-title">Sales Executives</h4>
        </div>
        <div class="card-body">
          <?php foreach ($salesExecutives as $exec): ?>
            <?php
              $execName = trim((string)($exec['name'] ?? ''));
              $execRole = trim((string)($exec['role'] ?? ''));
              $execEmail = trim((string)($exec['email'] ?? ''));
              $execPhone = trim((string)($exec['phone'] ?? ''));
              $execLanguages = trim((string)($exec['languages'] ?? ''));
              $execImage = trim((string)($exec['image'] ?? ''));
            ?>
            <div class="d-flex align-items-start gap-2 mb-3">
              <div class="avatar-md rounded-circle border border-2 border-light bg-light d-flex align-items-center justify-content-center overflow-hidden">
                <?php if ($execImage !== ''): ?>
                  <img src="<?= e($execImage) ?>"
                       data-fallback="<?= e($execImage) ?>"
                       onerror="this.remove();"
                       alt="<?= e($execName !== '' ? $execName : 'Sales Executive') ?>"
                       class="img-fluid rounded-circle"
                       style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                  <iconify-icon icon="solar:user-rounded-bold-duotone" class="fs-20 text-muted"></iconify-icon>
                <?php endif; ?>
              </div>
              <div class="flex-grow-1">
                <div class="fw-medium text-dark"><?= e($execName !== '' ? $execName : 'Sales Executive') ?></div>
                <?php if ($execRole !== ''): ?><div class="text-muted fs-13"><?= e($execRole) ?></div><?php endif; ?>
                <?php if ($execLanguages !== ''): ?><div class="text-muted fs-12">Languages: <?= e($execLanguages) ?></div><?php endif; ?>
                <?php if ($execEmail !== ''): ?>
                  <div class="mt-1"><a href="mailto:<?= e($execEmail) ?>" class="text-decoration-none fs-13"><?= e($execEmail) ?></a></div>
                <?php endif; ?>
                <?php if ($execPhone !== ''): ?>
                  <div class="mt-1"><a href="tel:<?= e($execPhone) ?>" class="text-decoration-none fs-13"><?= e($execPhone) ?></a></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
  <div class="col-xl-9 col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="position-relative">
          <?php if ($carouselImages): ?>
            <?php $carouselId = 'projectCarousel-' . ($projectId > 0 ? $projectId : uniqid()); ?>
            <div id="<?= e($carouselId) ?>" class="carousel slide" data-bs-ride="carousel">
              <div class="carousel-inner rounded">
                <?php foreach ($carouselImages as $index => $img): ?>
                  <?php
                  $payload = rtrim(strtr(base64_encode($img), '+/', '-_'), '=');
                  $signature = hash_hmac('sha256', $payload, app_key());
                  $imageToken = $payload . '.' . $signature;
                  ?>
                  <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <img src="<?= e(url('image-proxy.php?id=' . $imageToken)) ?>"
                         data-fallback="<?= e($img) ?>"
                         onerror="if (this.dataset.fallback) { this.src = this.dataset.fallback; this.removeAttribute('data-fallback'); }"
                         alt="<?= e($title) ?>"
                         class="d-block w-100"
                         style="height: 320px; object-fit: cover;">
                  </div>
                <?php endforeach; ?>
              </div>
              <?php if (count($carouselImages) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#<?= e($carouselId) ?>" data-bs-slide="prev">
                  <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                  <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#<?= e($carouselId) ?>" data-bs-slide="next">
                  <span class="carousel-control-next-icon" aria-hidden="true"></span>
                  <span class="visually-hidden">Next</span>
                </button>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 320px;">
              <span class="text-muted">No image</span>
            </div>
          <?php endif; ?>
          <span class="position-absolute top-0 start-0 p-2">
            <span class="badge bg-warning text-light px-2 py-1 fs-13"><?= e($projectStatus !== '' ? $projectStatus : 'Off-Plan') ?></span>
          </span>
          <?php if ($isCompleted): ?>
            <span class="position-absolute top-0 end-0 p-2">
              <span class="badge bg-success-subtle text-success border border-success-subtle fs-12">Completed</span>
            </span>
          <?php endif; ?>
        </div>
        <div class="d-flex flex-wrap justify-content-between my-3 gap-2">
          <div>
            <span class="fs-18 text-dark fw-medium"><?= e($title) ?></span>
            <?php if ($projectLocation !== ''): ?>
              <p class="d-flex align-items-center gap-1 mt-1 mb-0">
                <iconify-icon icon="solar:map-point-wave-bold-duotone" class="fs-18 text-primary"></iconify-icon>
                <?= e($projectLocation) ?>
              </p>
            <?php endif; ?>
          </div>
          <div>
            <ul class="list-inline float-end d-flex gap-1 mb-0 align-items-center">
              <li class="list-inline-item fs-20">
                <a href="#!" class="btn btn-light avatar-sm d-flex align-items-center justify-content-center text-dark fs-20">
                  <iconify-icon icon="solar:share-bold-duotone"></iconify-icon>
                </a>
              </li>
              <li class="list-inline-item fs-20">
                <a href="#!" class="btn btn-light avatar-sm d-flex align-items-center justify-content-center text-danger fs-20">
                  <iconify-icon icon="solar:heart-angle-bold-duotone"></iconify-icon>
                </a>
              </li>
            </ul>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <div class="avatar-sm bg-success-subtle rounded">
            <iconify-icon icon="solar:wallet-money-bold-duotone" class="fs-24 text-success avatar-title"></iconify-icon>
          </div>
          <p class="fw-medium text-dark fs-18 mb-0"><?= e($startingPrice !== '' ? $startingPrice : 'Price on request') ?></p>
        </div>
        <div class="bg-light-subtle p-2 mt-3 rounded border border-dashed">
          <div class="row align-items-center text-center g-2">
            <div class="col-xl-3 col-lg-4 col-md-6 col-6 border-end">
              <p class="text-muted mb-0 fs-15 fw-medium d-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:calendar-date-broken" class="fs-18 text-primary"></iconify-icon>
                <?= e($handoverDate !== '' ? $handoverDate : 'Handover TBA') ?>
              </p>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-6 border-end">
              <p class="text-muted mb-0 fs-15 fw-medium d-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:map-point-wave-bold-duotone" class="fs-18 text-primary"></iconify-icon>
                <?= e($projectLocation !== '' ? $projectLocation : 'District') ?>
              </p>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-6 border-end">
              <p class="text-muted mb-0 fs-15 fw-medium d-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:check-circle-broken" class="fs-18 text-primary"></iconify-icon>
                <?= e($projectStatus !== '' ? $projectStatus : 'Off-Plan') ?>
              </p>
            </div>
            <div class="col-xl-3 col-lg-4 col-md-6 col-6">
              <p class="text-muted mb-0 fs-15 fw-medium d-flex align-items-center justify-content-center gap-1">
                <iconify-icon icon="solar:home-2-broken" class="fs-18 text-primary"></iconify-icon>
                Apartments
              </p>
            </div>
          </div>
        </div>
        <h5 class="text-dark fw-medium mt-3">Some Facility :</h5>
        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
          <?php foreach ($facilityBadges as $badge): ?>
            <span class="badge bg-light-subtle text-muted border fw-medium fs-13 px-2 py-1"><?= e($badge) ?></span>
          <?php endforeach; ?>
        </div>
        <?php if ($facilityItems): ?>
          <h6 class="text-dark fw-medium mt-3">Facilities</h6>
          <div class="row g-2 mt-1">
            <?php foreach ($facilityItems as $facility): ?>
              <div class="col-md-4">
                <div class="border rounded p-2 h-100">
                  <?php if ($facility['image'] !== ''): ?>
                    <?php
                    $payload = rtrim(strtr(base64_encode($facility['image']), '+/', '-_'), '=');
                    $signature = hash_hmac('sha256', $payload, app_key());
                    $imageToken = $payload . '.' . $signature;
                    ?>
                    <img src="<?= e(url('image-proxy.php?id=' . $imageToken)) ?>"
                         data-fallback="<?= e($facility['image']) ?>"
                         onerror="if (this.dataset.fallback) { this.src = this.dataset.fallback; this.removeAttribute('data-fallback'); }"
                         alt="<?= e($facility['name']) ?>"
                         class="img-fluid rounded mb-2"
                         style="height: 140px; width: 100%; object-fit: cover;">
                  <?php endif; ?>
                  <div class="fw-medium text-dark"><?= e($facility['name']) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php if ($attachments): ?>
          <h6 class="text-dark fw-medium mt-3">Attachments</h6>
          <div class="list-group mt-2">
            <?php foreach ($attachments as $attachment): ?>
              <a href="<?= e($attachment['url']) ?>" target="_blank" rel="noopener" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span><?= e($attachment['title']) ?></span>
                <?php if ($attachment['type'] !== ''): ?>
                  <span class="badge bg-light-subtle text-muted border"><?= e(strtoupper($attachment['type'])) ?></span>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php if ($parkingItems): ?>
          <h6 class="text-dark fw-medium mt-3"><?= e($parkingTitle !== '' ? $parkingTitle : 'Parking') ?></h6>
          <div class="list-group mt-2">
            <?php foreach ($parkingItems as $item): ?>
              <?php if (is_array($item)): ?>
                <?php foreach ($item as $label => $value): ?>
                  <div class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?= e((string)$label) ?></span>
                    <span class="badge bg-light-subtle text-muted border"><?= e((string)$value) ?></span>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <h5 class="text-dark fw-medium mt-3">Property Details :</h5>
        <?php if ($descriptionHtml !== ''): ?>
          <div class="mt-2"><?= $descriptionHtml ?></div>
        <?php elseif (!empty($descriptions['general_facts'])): ?>
          <p class="mt-2"><?= nl2br(e((string)$descriptions['general_facts'])) ?></p>
        <?php else: ?>
          <p class="mt-2 text-muted">Details coming soon.</p>
        <?php endif; ?>
        <?php if (!empty($descriptions['location_benefits'])): ?>
          <h6 class="text-dark fw-medium mt-3">Location Description & Benefits</h6>
          <p class="mt-2"><?= nl2br(e((string)$descriptions['location_benefits'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($details['payment_plan']) && is_array($details['payment_plan'])): ?>
          <h6 class="text-dark fw-medium mt-3">Payment Plan</h6>
          <?php
          $plan = $details['payment_plan'];
          $planName = (string)($plan['name'] ?? '');
          $constructionPct = $plan['construction_pct'] ?? null;
          $postPct = $plan['post_handover_pct'] ?? null;
          $postMonths = $plan['post_handover_months'] ?? null;
          $eoi = '';
          if (!empty($plan['eoi_amount']) && is_array($plan['eoi_amount'])) {
            $eoiAmount = $plan['eoi_amount']['amount'] ?? null;
            $eoiCurrency = (string)($plan['eoi_amount']['currency'] ?? '');
            if (is_numeric($eoiAmount)) {
              $eoi = trim($eoiCurrency . ' ' . number_format((float)$eoiAmount));
            }
          }
          ?>
          <p class="mt-2 mb-0">
            <?= e($planName !== '' ? $planName : 'Payment plan available') ?>
            <?php if (is_numeric($constructionPct)): ?>
              | <?= e((string)$constructionPct) ?>% During construction
            <?php endif; ?>
            <?php if (is_numeric($postPct)): ?>
              | <?= e((string)$postPct) ?>% Post-handover
            <?php endif; ?>
            <?php if (is_numeric($postMonths)): ?>
              | <?= e((string)$postMonths) ?> months
            <?php endif; ?>
            <?php if ($eoi !== ''): ?>
              | EOI <?= e($eoi) ?>
            <?php endif; ?>
          </p>
        <?php endif; ?>
        <?php if (!empty($project['new_payment_plans']) && is_array($project['new_payment_plans'])): ?>
          <h6 class="text-dark fw-medium mt-3">Payment Plans</h6>
          <div class="row g-2 mt-1">
            <?php foreach ($project['new_payment_plans'] as $plan): ?>
              <?php
              if (!is_array($plan)) continue;
              $planTitle = (string)($plan['title'] ?? 'Payment plan');
              $info = is_array($plan['info'] ?? null) ? $plan['info'] : [];
              $onBooking = $info['on_booking_percent'] ?? null;
              $onConstruction = $info['on_construction_percent'] ?? null;
              $onHandover = $info['on_handover_percent'] ?? null;
              $postHandover = $info['post_handover_percent'] ?? null;
              $timeline = (string)($plan['timeline_quarter'] ?? '');
              $headingPercentages = is_array($plan['heading_percentages'] ?? null) ? $plan['heading_percentages'] : [];
              $milestones = is_array($plan['milestones'] ?? null) ? $plan['milestones'] : [];
              $fees = is_array($plan['fees'] ?? null) ? $plan['fees'] : [];
              $hasInfoPerc = ($onBooking !== null || $onConstruction !== null || $onHandover !== null || $postHandover !== null);
              $percentageParts = [];
              if ($hasInfoPerc) {
                if ($onBooking !== null) $percentageParts[] = 'Booking: ' . (string)$onBooking . '%';
                if ($onConstruction !== null) $percentageParts[] = 'Construction: ' . (string)$onConstruction . '%';
                if ($onHandover !== null) $percentageParts[] = 'Handover: ' . (string)$onHandover . '%';
                if ($postHandover !== null) $percentageParts[] = 'Post-handover: ' . (string)$postHandover . '%';
              } elseif ($headingPercentages) {
                foreach ($headingPercentages as $label => $value) {
                  $percentageParts[] = trim((string)$label) . ': ' . trim((string)$value);
                }
              } elseif ($milestones) {
                foreach ($milestones as $milestone) {
                  if (!is_array($milestone)) continue;
                  $label = (string)($milestone['milestone'] ?? '');
                  $value = (string)($milestone['percentage'] ?? '');
                  if ($label !== '' && $value !== '') {
                    $percentageParts[] = $label . ': ' . $value;
                  }
                }
              }
              $eoiFee = '';
              foreach ($fees as $fee) {
                if (!is_array($fee)) continue;
                $type = strtolower((string)($fee['type'] ?? ''));
                $amount = (string)($fee['amount'] ?? '');
                if ($type === 'eoi' && $amount !== '') {
                  $eoiFee = $amount;
                  break;
                }
              }
              if ($eoiFee === '' && !empty($plan['conditions']['EOI'])) {
                $eoiFee = (string)$plan['conditions']['EOI'];
              }
              ?>
              <div class="col-md-6">
                <div class="border rounded p-2 h-100">
                  <div class="fw-medium text-dark"><?= e($planTitle) ?></div>
                  <?php if ($timeline !== ''): ?>
                    <div class="text-muted fs-13">Timeline: <?= e($timeline) ?></div>
                  <?php endif; ?>
                  <?php if ($percentageParts): ?>
                    <div class="text-muted fs-13 mt-1"><?= e(implode(' | ', $percentageParts)) ?></div>
                  <?php endif; ?>
                  <?php if ($eoiFee !== ''): ?>
                    <div class="text-muted fs-13 mt-1">EOI: <?= e($eoiFee) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php
        $unitTypes = [];
        if (!empty($details['unit_types']) && is_array($details['unit_types'])) {
          $unitTypes = $details['unit_types'];
        } elseif (!empty($project['typical_units']) && is_array($project['typical_units'])) {
          $unitTypes = $project['typical_units'];
        }
        ?>
        <?php if ($unitTypes): ?>
          <h6 class="text-dark fw-medium mt-3">Unit Types</h6>
          <div class="row g-2 mt-1">
            <?php foreach ($unitTypes as $unit): ?>
              <?php
              if (!is_array($unit)) continue;
              $label = (string)($unit['unit_type_label'] ?? $unit['unit_type'] ?? 'Unit');
              $beds = $unit['beds'] ?? ($unit['bedroom'] ?? null);
              $unitPrice = '';
              $unitSize = '';
              if (!empty($unit['price_from']) && is_array($unit['price_from'])) {
                $uAmount = $unit['price_from']['amount'] ?? null;
                $uCurrency = (string)($unit['price_from']['currency'] ?? '');
                if (is_numeric($uAmount)) {
                  $unitPrice = trim($uCurrency . ' ' . number_format((float)$uAmount));
                }
              }
              if ($unitPrice === '' && !empty($unit['lowest_price'])) {
                $uCurrency = (string)($unit['display_currency'] ?? '');
                if (is_numeric($unit['lowest_price'])) {
                  $unitPrice = trim($uCurrency . ' ' . number_format((float)$unit['lowest_price'], 0, '.', ','));
                } else {
                  $unitPrice = trim($uCurrency . ' ' . (string)$unit['lowest_price']);
                }
              }
              $unitAreaUnit = (string)($unit['area_size'] ?? ($unit['area_unit'] ?? ($project['area_size'] ?? ($project['area_unit'] ?? ''))));
              $lowestArea = $unit['lowest_area'] ?? ($unit['area_start'] ?? null);
              $highestArea = $unit['highest_area'] ?? ($unit['area_end'] ?? null);
              if (is_numeric($lowestArea) && is_numeric($highestArea)) {
                $unitSize = number_format((float)$lowestArea, 0, '.', ',') . '-' . number_format((float)$highestArea, 0, '.', ',');
              } elseif (is_numeric($lowestArea)) {
                $unitSize = 'From ' . number_format((float)$lowestArea, 0, '.', ',');
              } elseif (is_numeric($highestArea)) {
                $unitSize = 'Up to ' . number_format((float)$highestArea, 0, '.', ',');
              }
              if ($unitSize === '' && (is_numeric($project['area_start'] ?? null) || is_numeric($project['area_end'] ?? null))) {
                $projStart = $project['area_start'] ?? null;
                $projEnd = $project['area_end'] ?? null;
                if (is_numeric($projStart) && is_numeric($projEnd)) {
                  $unitSize = number_format((float)$projStart, 0, '.', ',') . '-' . number_format((float)$projEnd, 0, '.', ',');
                } elseif (is_numeric($projStart)) {
                  $unitSize = 'From ' . number_format((float)$projStart, 0, '.', ',');
                } elseif (is_numeric($projEnd)) {
                  $unitSize = 'Up to ' . number_format((float)$projEnd, 0, '.', ',');
                }
              }
              if ($unitSize !== '' && $unitAreaUnit !== '') {
                $unitSize .= ' ' . $unitAreaUnit;
              }
              ?>
              <div class="col-md-4">
                <div class="border rounded p-2 h-100">
                  <div class="fw-medium text-dark"><?= e($label) ?></div>
                  <div class="text-muted fs-13">
                    <?= e(is_numeric($beds) ? ((int)$beds . ' Beds') : 'Beds TBA') ?>
                    <?= $unitPrice !== '' ? (' | From ' . e($unitPrice)) : '' ?>
                    <?= $unitSize !== '' ? (' | ' . e($unitSize)) : '' ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php
        $nearbyPlaces = [];
        if (!empty($details['nearby_places']) && is_array($details['nearby_places'])) {
          $nearbyPlaces = $details['nearby_places'];
        } elseif (!empty($project['nearby_locations']) && is_array($project['nearby_locations'])) {
          $nearbyPlaces = $project['nearby_locations'];
        }
        ?>
        <?php if ($nearbyPlaces): ?>
          <h6 class="text-dark fw-medium mt-3">Nearby Places</h6>
          <div class="row g-2 mt-1">
            <?php foreach ($nearbyPlaces as $place): ?>
              <?php
              if (!is_array($place)) continue;
              $placeName = (string)($place['name'] ?? 'Place');
              $distance = $place['distance_km'] ?? ($place['distance'] ?? null);
              ?>
              <div class="col-md-6">
                <div class="d-flex align-items-center justify-content-between border rounded p-2">
                  <span class="text-muted"><?= e($placeName) ?></span>
                  <span class="badge bg-light-subtle text-muted border">
                    <?= e(is_numeric($distance) ? ((float)$distance . ' km') : ((string)$distance !== '' ? (string)$distance : 'Distance TBA')) ?>
                  </span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="card mt-3">
      <div class="card-body">
        <div class="row g-3">
          <?php if (!empty($project['finishing'])): ?>
            <div class="col-md-6">
              <h6 class="mb-2">Finishing & Materials</h6>
              <p class="text-muted mb-0"><?= nl2br(e((string)$project['finishing'])) ?></p>
            </div>
          <?php endif; ?>
          <?php if (!empty($project['kitchen'])): ?>
            <div class="col-md-6">
              <h6 class="mb-2">Kitchen & Appliances</h6>
              <p class="text-muted mb-0"><?= nl2br(e((string)$project['kitchen'])) ?></p>
            </div>
          <?php endif; ?>
          <?php if (!empty($project['furnishing'])): ?>
            <div class="col-md-6">
              <h6 class="mb-2">Furnishing</h6>
              <p class="text-muted mb-0"><?= nl2br(e((string)$project['furnishing'])) ?></p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$mapQuery = $projectLocation !== '' ? $projectLocation : $title;
$mapUrl = $mapEmbedUrl !== '' ? $mapEmbedUrl : ('https://maps.google.com/maps?width=1980&height=400&hl=en&q=' . urlencode($mapQuery) . '&t=&z=12&ie=UTF8&iwloc=B&output=embed');
if ($mapImageUrl === '' && $mapLinkUrl === '') {
  $mapLinkUrl = 'https://maps.google.com/maps?q=' . urlencode($mapQuery);
}
?>
<div class="row mt-3">
  <div class="col-12">
    <?php if ($mapImageUrl !== ''): ?>
      <div class="card">
        <div class="card-body">
          <a href="<?= e($mapLinkUrl !== '' ? $mapLinkUrl : $mapUrl) ?>" target="_blank" rel="noopener">
            <img src="<?= e($mapImageUrl) ?>" alt="<?= e($title) ?> map" class="img-fluid rounded" style="width: 100%; height: 360px; object-fit: cover;">
          </a>
          <?php if ($mapLinkUrl !== ''): ?>
            <div class="mt-2">
              <a href="<?= e($mapLinkUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">Open in Google Maps</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="card">
        <div class="card-body">
          <a href="<?= e($mapLinkUrl !== '' ? $mapLinkUrl : $mapUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">Open in Google Maps</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../app/Views/layouts/app.php';

if ($projectId > 0):
?>
<script>
  fetch(<?= json_encode($apiUrl, JSON_UNESCAPED_SLASHES) ?>)
    .then((r) => r.json())
    .then((data) => console.log('[External Project Detail API]', data))
    .catch((err) => console.error('[External Project Detail API]', err));
</script>
<?php
endif;

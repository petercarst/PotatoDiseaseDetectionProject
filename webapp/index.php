<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$dbError = null;
$stats = ['total' => 0, 'healthy' => 0, 'early_blight' => 0, 'late_blight' => 0];
$recentScans = [];

try {
    $stats = get_stats();
    $recentScans = get_recent_scans(6);
} catch (Throwable $ex) {
    $dbError = 'Could not connect to the database. Import webapp/database/schema.sql into MySQL and check config/config.php.';
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="container py-5">

  <!-- Hero -->
  <section class="text-center mx-auto mb-5" style="max-width: 760px;">
    <span class="ui-eyebrow"><i class="bi bi-stars me-1"></i>Computer Vision for Agriculture</span>
    <h1 class="font-display fw-bold ui-hero-title mt-3 mb-3 text-gradient-uhai">Diagnose Potato Leaves Instantly</h1>
    <p class="ui-lede">
      Upload a photo of a potato plant leaf and Uhai Intelligence's convolutional neural network
      will screen it for <strong>Early Blight</strong> and <strong>Late Blight</strong> in seconds.
    </p>
    <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
      <span class="ui-pill"><i class="bi bi-lightning-charge-fill text-life"></i>Instant AI diagnosis</span>
      <span class="ui-pill"><i class="bi bi-diagram-3-fill text-intel"></i>3-class CNN model</span>
      <span class="ui-pill"><i class="bi bi-shield-lock-fill text-life"></i>Runs on your own server</span>
    </div>
  </section>

  <?php if ($dbError): ?>
    <div class="alert alert-danger d-flex align-items-start gap-2 mb-4" role="alert">
      <i class="bi bi-database-fill-exclamation fs-5"></i>
      <div><?= e($dbError) ?></div>
    </div>
  <?php endif; ?>

  <!-- Stats -->
  <section class="row g-3 mb-4" id="statsRow">
    <div class="col-6 col-lg-3">
      <div class="ui-stat-card tw-transition tw-duration-200 hover:tw-shadow-md hover:-tw-translate-y-0.5">
        <i class="bi bi-images"></i>
        <div>
          <div class="ui-stat-num" data-stat="total"><?= $stats['total'] ?></div>
          <div class="ui-stat-label">Total Scans</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="ui-stat-card ui-stat-life tw-transition tw-duration-200 hover:tw-shadow-md hover:-tw-translate-y-0.5">
        <i class="bi bi-patch-check-fill"></i>
        <div>
          <div class="ui-stat-num" data-stat="healthy"><?= $stats['healthy'] ?></div>
          <div class="ui-stat-label">Healthy</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="ui-stat-card ui-stat-warning tw-transition tw-duration-200 hover:tw-shadow-md hover:-tw-translate-y-0.5">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
          <div class="ui-stat-num" data-stat="early_blight"><?= $stats['early_blight'] ?></div>
          <div class="ui-stat-label">Early Blight</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="ui-stat-card ui-stat-danger tw-transition tw-duration-200 hover:tw-shadow-md hover:-tw-translate-y-0.5">
        <i class="bi bi-x-octagon-fill"></i>
        <div>
          <div class="ui-stat-num" data-stat="late_blight"><?= $stats['late_blight'] ?></div>
          <div class="ui-stat-label">Late Blight</div>
        </div>
      </div>
    </div>
  </section>

  <div class="row g-4">
    <!-- Upload & Scan -->
    <div class="col-lg-7">
      <div class="ui-card h-100">
        <div class="ui-card-header">
          <h2 class="h5 fw-bold mb-0 font-display">Scan a Leaf</h2>
          <p class="ui-brand-sub mb-0">JPG, PNG or WEBP &middot; up to 5&nbsp;MB</p>
        </div>
        <div class="ui-card-body">

          <div class="alert alert-danger d-none align-items-start gap-2" id="scanError" role="alert">
            <i class="bi bi-exclamation-octagon-fill fs-5"></i>
            <div id="scanErrorText"></div>
          </div>

          <form id="scanForm">
            <div id="dropzone" class="ui-dropzone" tabindex="0" role="button" aria-label="Upload a leaf image">
              <input type="file" id="fileInput" name="leaf" accept="image/jpeg,image/png,image/webp" class="d-none">
              <input type="file" id="cameraInput" accept="image/jpeg,image/png,image/webp" capture="environment" class="d-none">

              <div id="dropzonePlaceholder" class="text-center py-4">
                <div class="ui-dropzone-icon mb-3"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                <p class="fw-semibold mb-1">Drag &amp; drop a leaf photo here</p>
                <p class="ui-brand-sub mb-2">or choose one below</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                  <button type="button" id="browseBtn" class="btn ui-btn-ghost btn-sm">
                    <i class="bi bi-folder2-open me-1"></i>Upload Image
                  </button>
                  <button type="button" id="cameraBtn" class="btn ui-btn-ghost btn-sm">
                    <i class="bi bi-camera-fill me-1"></i>Take Photo
                  </button>
                </div>
              </div>

              <div id="previewWrap" class="d-none">
                <div class="ui-scan-frame">
                  <img id="previewImg" alt="Selected leaf preview">
                  <div class="ui-scan-line"></div>
                </div>
              </div>
            </div>

            <div class="d-flex gap-2 mt-3">
              <button type="submit" id="scanBtn" class="btn ui-btn-gradient flex-grow-1" disabled>
                <i class="bi bi-search me-1"></i><span id="scanBtnText">Scan Leaf</span>
              </button>
              <button type="button" id="resetBtn" class="btn ui-btn-ghost d-none">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
              </button>
            </div>
          </form>

          <!-- Result -->
          <div id="resultPanel" class="ui-result d-none mt-4">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="ui-result-icon" id="resultIcon"><i class="bi bi-patch-check-fill"></i></div>
              <div>
                <div class="ui-brand-sub">Diagnosis</div>
                <div class="h4 mb-0 fw-bold font-display" id="resultClass">&mdash;</div>
              </div>
              <div class="ms-auto text-end">
                <div class="ui-brand-sub">Confidence</div>
                <div class="h4 mb-0 fw-bold font-display" id="resultConfidence">&mdash;</div>
              </div>
            </div>
            <div class="ui-progress mb-3">
              <div class="ui-progress-bar" id="resultBar" style="width:0%"></div>
            </div>
            <p class="mb-2" id="resultSummary"></p>
            <p class="mb-0 ui-advice"><i class="bi bi-lightbulb-fill me-1"></i><span id="resultAdvice"></span></p>
          </div>

        </div>
      </div>
    </div>

    <!-- Recent scans -->
    <div class="col-lg-5">
      <div class="ui-card h-100">
        <div class="ui-card-header d-flex align-items-center justify-content-between">
          <div>
            <h2 class="h5 fw-bold mb-0 font-display">Recent Scans</h2>
            <p class="ui-brand-sub mb-0">Latest activity from this AI engine</p>
          </div>
          <a href="history.php" class="ui-link-arrow">View all <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="ui-card-body">
          <div id="recentList" class="d-flex flex-column gap-2">
            <?php if (!$recentScans): ?>
              <p class="ui-brand-sub text-center py-4 mb-0" id="recentEmpty">No scans yet &mdash; upload a leaf to get started.</p>
            <?php else: foreach ($recentScans as $scan): $info = disease_info($scan['predicted_class']); ?>
              <div class="ui-recent-item">
                <img src="<?= e(UPLOAD_URL . $scan['stored_name']) ?>" alt="">
                <div class="flex-grow-1">
                  <div class="fw-semibold small"><?= e($scan['predicted_class']) ?></div>
                  <div class="ui-brand-sub"><?= number_format((float) $scan['confidence'], 1) ?>% &middot; <?= e(time_ago($scan['created_at'])) ?></div>
                </div>
                <span class="ui-badge ui-badge-<?= e($info['color']) ?>"><i class="bi <?= e($info['icon']) ?>"></i></span>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<template id="recentItemTemplate">
  <div class="ui-recent-item">
    <img alt="">
    <div class="flex-grow-1">
      <div class="fw-semibold small" data-field="class"></div>
      <div class="ui-brand-sub"><span data-field="confidence"></span>% &middot; <span data-field="time"></span></div>
    </div>
    <span class="ui-badge"><i class="bi"></i></span>
  </div>
</template>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

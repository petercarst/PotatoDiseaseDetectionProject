<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$dbError = null;
$scans = [];
$total = 0;
$perPage = 12;
$page = max(1, (int) ($_GET['page'] ?? 1));
$filter = $_GET['filter'] ?? 'all';

$filterMap = [
    'healthy' => 'Healthy',
    'early'   => 'Early Blight',
    'late'    => 'Late Blight',
];

try {
    $pdo = db();
    $where = '';
    $params = [];
    if (isset($filterMap[$filter])) {
        $where = 'WHERE predicted_class = :class';
        $params[':class'] = $filterMap[$filter];
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM scans $where");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare("SELECT * FROM scans $where ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $scans = $stmt->fetchAll();
} catch (Throwable $ex) {
    $dbError = 'Could not connect to the database. Import webapp/database/schema.sql into MySQL and check config/config.php.';
}

$totalPages = max(1, (int) ceil($total / $perPage));

function filterLink(string $key, string $current): string
{
    $active = $key === $current ? 'active' : '';
    $label = ['all' => 'All', 'healthy' => 'Healthy', 'early' => 'Early Blight', 'late' => 'Late Blight'][$key];
    $qs = $key === 'all' ? '' : '?filter=' . $key;
    return "<a href=\"history.php{$qs}\" class=\"ui-filter-pill {$active}\">{$label}</a>";
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="container py-5">

  <section class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
      <span class="ui-eyebrow"><i class="bi bi-clock-history me-1"></i>Archive</span>
      <h1 class="font-display fw-bold h2 mt-2 mb-0 text-gradient-uhai">Scan History</h1>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <?= filterLink('all', $filter) ?>
      <?= filterLink('healthy', $filter) ?>
      <?= filterLink('early', $filter) ?>
      <?= filterLink('late', $filter) ?>
    </div>
  </section>

  <?php if ($dbError): ?>
    <div class="alert alert-danger d-flex align-items-start gap-2 mb-4" role="alert">
      <i class="bi bi-database-fill-exclamation fs-5"></i>
      <div><?= e($dbError) ?></div>
    </div>
  <?php elseif (!$scans): ?>
    <div class="ui-card">
      <div class="ui-card-body text-center py-5">
        <i class="bi bi-inboxes ui-empty-icon"></i>
        <p class="ui-brand-sub mt-3 mb-0">No scans found for this filter.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="ui-card">
      <div class="table-responsive">
        <table class="table ui-table align-middle mb-0">
          <thead>
            <tr>
              <th>Leaf</th>
              <th>Diagnosis</th>
              <th>Confidence</th>
              <th>Scanned</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody id="historyBody">
            <?php foreach ($scans as $scan): $info = disease_info($scan['predicted_class']); ?>
              <tr data-id="<?= (int) $scan['id'] ?>">
                <td>
                  <img src="<?= e(UPLOAD_URL . $scan['stored_name']) ?>" class="ui-thumb" alt="">
                </td>
                <td>
                  <span class="ui-badge ui-badge-<?= e($info['color']) ?>">
                    <i class="bi <?= e($info['icon']) ?> me-1"></i><?= e($scan['predicted_class']) ?>
                  </span>
                </td>
                <td class="fw-semibold"><?= number_format((float) $scan['confidence'], 1) ?>%</td>
                <td class="ui-brand-sub"><?= e(date('M j, Y g:i A', strtotime($scan['created_at']))) ?></td>
                <td class="text-end">
                  <button class="btn btn-sm ui-btn-delete" data-delete="<?= (int) $scan['id'] ?>" title="Delete scan">
                    <i class="bi bi-trash3-fill"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if ($totalPages > 1): ?>
      <nav class="d-flex justify-content-center mt-4">
        <ul class="pagination ui-pagination">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $p ?><?= $filter !== 'all' ? '&filter=' . e($filter) : '' ?>"><?= $p ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
  <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

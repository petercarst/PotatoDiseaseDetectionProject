<?php
/**
 * uhAI Intelligence - shared helpers
 */

require_once __DIR__ . '/../config/database.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Static knowledge base for each class the model can predict.
 */
function disease_info(string $class): array
{
    $catalog = [
        'Healthy' => [
            'status'      => 'healthy',
            'color'       => 'life',
            'icon'        => 'bi-patch-check-fill',
            'summary'     => 'No signs of blight detected. This leaf looks healthy.',
            'advice'      => 'Keep up routine monitoring, proper spacing and balanced irrigation to maintain plant vigor.',
        ],
        'Early Blight' => [
            'status'      => 'diseased',
            'color'       => 'warning',
            'icon'        => 'bi-exclamation-triangle-fill',
            'summary'     => 'Caused by Alternaria solani. Look for dark concentric "target-ring" spots on older leaves.',
            'advice'      => 'Remove infected foliage, rotate crops, and apply a recommended fungicide at the first sign of spread.',
        ],
        'Late Blight' => [
            'status'      => 'diseased',
            'color'       => 'danger',
            'icon'        => 'bi-x-octagon-fill',
            'summary'     => 'Caused by Phytophthora infestans. Spreads fast in cool, wet weather and can destroy a crop within days.',
            'advice'      => 'Isolate and destroy infected plants immediately, improve airflow, and apply a systemic fungicide without delay.',
        ],
    ];

    return $catalog[$class] ?? [
        'status'  => 'diseased',
        'color'   => 'secondary',
        'icon'    => 'bi-question-circle-fill',
        'summary' => 'Unrecognized class returned by the model.',
        'advice'  => 'Please try scanning again with a clearer image.',
    ];
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);

    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 2592000) return floor($diff / 86400) . 'd ago';

    return date('M j, Y', strtotime($datetime));
}

function get_stats(): array
{
    $row = db()->query("
        SELECT
            COUNT(*)                                       AS total,
            SUM(status = 'healthy')                        AS healthy,
            SUM(predicted_class = 'Early Blight')           AS early_blight,
            SUM(predicted_class = 'Late Blight')            AS late_blight
        FROM scans
    ")->fetch();

    return [
        'total'        => (int) ($row['total'] ?? 0),
        'healthy'      => (int) ($row['healthy'] ?? 0),
        'early_blight' => (int) ($row['early_blight'] ?? 0),
        'late_blight'  => (int) ($row['late_blight'] ?? 0),
    ];
}

function get_recent_scans(int $limit = 8): array
{
    $stmt = db()->prepare('SELECT * FROM scans ORDER BY created_at DESC, id DESC LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

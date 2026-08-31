<?php
/**
 * uhAI Intelligence - lightweight proxy check for the FastAPI prediction engine.
 * Called by the browser so the health check never has to fight CORS directly.
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json');

$pingUrl = preg_replace('#/predict$#', '/ping', PREDICT_API_URL);

$ch = curl_init($pingUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 3,
    CURLOPT_CONNECTTIMEOUT => 3,
]);
$response = curl_exec($ch);
$ok = $response !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
curl_close($ch);

echo json_encode(['online' => $ok]);

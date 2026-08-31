<?php
/**
 * Uhai Intelligence - deletes a scan record and its stored image.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid scan id.']);
    exit;
}

$stmt = db()->prepare('SELECT stored_name FROM scans WHERE id = :id');
$stmt->execute([':id' => $id]);
$scan = $stmt->fetch();

if (!$scan) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Scan not found.']);
    exit;
}

$path = UPLOAD_DIR . $scan['stored_name'];
if (is_file($path)) {
    unlink($path);
}

$del = db()->prepare('DELETE FROM scans WHERE id = :id');
$del->execute([':id' => $id]);

echo json_encode(['success' => true]);

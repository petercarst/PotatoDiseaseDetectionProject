<?php
/**
 * Uhai Intelligence - handles a leaf upload:
 *   1. validates the image
 *   2. forwards it to the FastAPI/TensorFlow model
 *   3. stores the result in MySQL
 *   4. returns JSON for the front-end to render
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

function fail(int $httpCode, string $message): void
{
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, 'Method not allowed.');
}

if (!isset($_FILES['leaf']) || $_FILES['leaf']['error'] === UPLOAD_ERR_NO_FILE) {
    fail(400, 'Please choose a leaf image first.');
}

$file = $_FILES['leaf'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    fail(400, 'Upload failed. Please try again.');
}

if ($file['size'] > MAX_FILE_SIZE) {
    fail(400, 'That image is larger than 5 MB. Please choose a smaller file.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, ALLOWED_MIME_TYPES, true)) {
    fail(400, 'Unsupported file type. Please upload a JPG, PNG or WEBP image.');
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extension, ALLOWED_EXTENSIONS, true)) {
    $extension = str_replace('image/', '', $mime) === 'jpeg' ? 'jpg' : str_replace('image/', '', $mime);
}

if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true) && !is_dir(UPLOAD_DIR)) {
    fail(500, 'Server storage is not writable.');
}

$storedName = bin2hex(random_bytes(12)) . '.' . $extension;
$destination = UPLOAD_DIR . $storedName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    fail(500, 'Could not save the uploaded image.');
}

// --- Forward to the FastAPI / TensorFlow model --------------------------
$ch = curl_init(PREDICT_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => PREDICT_API_TIMEOUT,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => [
        'file' => new CURLFile($destination, $mime, $storedName),
    ],
]);
$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    unlink($destination);
    fail(502, 'The AI engine is unreachable. Make sure the FastAPI server (api/main.py) is running on port 8002, then try again.'
        . ($curlError ? " ({$curlError})" : ''));
}

$prediction = json_decode($response, true);

if ($httpCode === 422) {
    unlink($destination);
    $detail = $prediction['detail'] ?? null;
    $message = is_array($detail) && !empty($detail['message'])
        ? $detail['message']
        : "This doesn't look like a potato leaf photo. Please upload a clear, close-up photo of a single leaf.";
    fail(422, $message);
}

if ($httpCode !== 200) {
    unlink($destination);
    fail(502, 'The AI engine is unreachable. Make sure the FastAPI server (api/main.py) is running on port 8002, then try again.');
}

if (!isset($prediction['class'], $prediction['confidence'])) {
    unlink($destination);
    fail(502, 'The AI engine returned an unexpected response.');
}

$predictedClass = $prediction['class'];
$confidence = round(((float) $prediction['confidence']) * 100, 3);
$info = disease_info($predictedClass);

// --- Persist to MySQL -----------------------------------------------------
$stmt = db()->prepare('
    INSERT INTO scans (original_name, stored_name, predicted_class, confidence, status)
    VALUES (:original_name, :stored_name, :predicted_class, :confidence, :status)
');
$stmt->execute([
    ':original_name'   => $file['name'],
    ':stored_name'     => $storedName,
    ':predicted_class' => $predictedClass,
    ':confidence'      => $confidence,
    ':status'          => $info['status'],
]);

echo json_encode([
    'success' => true,
    'scan' => [
        'id'              => db()->lastInsertId(),
        'image_url'       => UPLOAD_URL . $storedName,
        'predicted_class' => $predictedClass,
        'confidence'      => $confidence,
        'status'          => $info['status'],
        'color'           => $info['color'],
        'icon'            => $info['icon'],
        'summary'         => $info['summary'],
        'advice'          => $info['advice'],
        'created_at'      => 'just now',
    ],
]);

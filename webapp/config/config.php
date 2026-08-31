<?php
/**
 * Uhai Intelligence - Potato Leaf Disease Detection
 * Global application configuration
 */

// --- Database -------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'potato_disease_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// --- Prediction API (FastAPI + TensorFlow model, see /api/main.py) --------
define('PREDICT_API_URL', 'http://127.0.0.1:8002/predict');
define('PREDICT_API_TIMEOUT', 30); // seconds

// --- Uploads ----------------------------------------------------------------
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// --- App ----------------------------------------------------------------
define('APP_NAME', 'Uhai Intelligence');
define('APP_TAGLINE', 'AI-Powered Potato Leaf Disease Detection');

date_default_timezone_set('Africa/Nairobi');
error_reporting(E_ALL);
ini_set('display_errors', '0'); // never leak stack traces to the browser

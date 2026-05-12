<?php
/**
 * Simple function to load .env file variables
 */
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Load .env from root
loadEnv(__DIR__ . '/../.env');

// Helper function to get config (checks getenv first, then $_ENV, then default)
function getConfig($key, $default = '') {
    $val = getenv($key);
    if ($val !== false) return $val;
    return $_ENV[$key] ?? $default;
}

// Database configuration
$dbUrl = getConfig('DATABASE_URL');
if ($dbUrl) {
    // Check if it's a postgres URL (Supabase standard)
    if (strpos($dbUrl, 'postgres') === 0) {
        $url = parse_url($dbUrl);
        define('DB_TYPE', 'pgsql');
        define('DB_HOST', $url['host']);
        define('DB_PORT', $url['port'] ?? 5432);
        define('DB_USER', urldecode($url['user']));
        define('DB_PASS', isset($url['pass']) ? urldecode($url['pass']) : '');
        define('DB_NAME', substr($url['path'], 1));
    } else {
        // Fallback to MySQL parsing if it's a mysql URL
        $url = parse_url($dbUrl);
        define('DB_TYPE', 'mysql');
        define('DB_HOST', $url['host']);
        define('DB_PORT', $url['port'] ?? 3306);
        define('DB_USER', $url['user']);
        define('DB_PASS', $url['pass'] ?? '');
        define('DB_NAME', substr($url['path'], 1));
    }
} else {
    // Default to local MySQL
    define('DB_TYPE', 'mysql');
    define('DB_HOST', getConfig('DB_HOST', 'localhost'));
    define('DB_PORT', getConfig('DB_PORT', 3306));
    define('DB_USER', getConfig('DB_USER', 'root'));
    define('DB_PASS', getConfig('DB_PASS', ''));
    define('DB_NAME', getConfig('DB_NAME', 'pawn_scanner_db'));
}

// Gemini API Keys
define('API_KEYS', array_filter([
    getConfig('GEMINI_API_KEY_1'),
    getConfig('GEMINI_API_KEY_2')
]));

// Cloudinary Configuration
define('CLOUDINARY_CLOUD_NAME', getConfig('CLOUDINARY_CLOUD_NAME'));
define('CLOUDINARY_API_KEY', getConfig('CLOUDINARY_API_KEY'));
define('CLOUDINARY_API_SECRET', getConfig('CLOUDINARY_API_SECRET'));

// Branches List
define('BRANCHES', [
    'Kiribathgoda', 'Waththala 1', 'Waththala 2', 'Waththala 3',
    'Kadawatha', 'Homagama', 'Dehiwala', 'Kottawa',
    'Office', 'Waththala 4', 'Dematagoda', 'Panadura', 'Boralla'
]);

// Directories
// On Vercel, we must use /tmp for temporary file storage
if (getenv('VERCEL') || getenv('VERCEL_ENV')) {
    define('UPLOAD_DIR', '/tmp/uploads/');
    define('SPLIT_DIR', '/tmp/uploads/splits/');
} else {
    define('UPLOAD_DIR', __DIR__ . '/../uploads/');
    define('SPLIT_DIR', __DIR__ . '/../uploads/splits/');
}

// Ensure directories exist (only if writable)
if (!getenv('VERCEL') && !getenv('VERCEL_ENV')) {
    if (!file_exists(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0777, true);
    if (!file_exists(SPLIT_DIR)) @mkdir(SPLIT_DIR, 0777, true);
} else {
    // On Vercel, we still need to create /tmp/uploads
    if (!file_exists(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0777, true);
    if (!file_exists(SPLIT_DIR)) @mkdir(SPLIT_DIR, 0777, true);
}
?>

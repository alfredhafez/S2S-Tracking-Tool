<?php
/**
 * S2S Postback Testing Tool - Configuration Example
 * Copy this file to config.php and update the values
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 's2s_tracking');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'S2S Postback Testing Tool');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost'); // No trailing slash

// Admin Panel Configuration
// Leave empty to disable password protection
define('ADMIN_PASSWORD', ''); // Example: 'admin123'

// Security
define('SESSION_NAME', 's2s_session');
define('CSRF_TOKEN_NAME', 's2s_csrf_token');

// cURL Configuration
define('CURL_TIMEOUT', 15); // seconds
define('CURL_FOLLOW_REDIRECTS', false);
define('CURL_SSL_VERIFY', true); // Set to false for testing only

// Timezone
date_default_timezone_set('UTC');

// Error Reporting (set to 0 for production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
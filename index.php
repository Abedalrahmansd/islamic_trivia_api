<?php
// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'config/database.php';
require_once 'config/cors.php';
require_once 'config/constants.php';
require_once 'utils/Response.php';
require_once 'routes/api.php';

// Set CORS headers
setCorsHeaders();

// Get request details
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', trim($uri, '/'));

// Remove empty elements and get endpoint parts
$endpoint = array_filter($uri);
$endpoint = array_values($endpoint);

// Remove 'islamic-trivia-api' from path if present
if (($endpoint[0] ?? '') === 'islamic-trivia-api') {
    array_shift($endpoint);
}
// Remove 'api' from path if present
if (($endpoint[0] ?? '') === 'api') {
    array_shift($endpoint);
}

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();

    // Route the request
    $router = new ApiRouter($db);
    $router->route($method, $endpoint);
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    Response::error('Internal server error', 500, null, 'INTERNAL_ERROR');
}
?>
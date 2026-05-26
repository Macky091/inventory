<?php
/**
 * Front Controller — index.php
 * Every request enters here. The ?page= parameter determines which page is loaded.
 *
 * Supported routes:
 *   login      — Login form
 *   register   — Registration form
 *   logout     — Destroys session and redirects
 *   dashboard  — Main dashboard (auth required)
 *   products   — Product CRUD (auth required)
 *   users      — User list (admin required)
 *   (default)  — Redirects based on auth status
 */

// ── Bootstrap ─────────────────────────────────────────────────
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/user.php';
require_once __DIR__ . '/includes/product.php';

// ── Route ──────────────────────────────────────────────────────
$page = preg_replace('/[^a-z0-9_\-]/', '', strtolower($_GET['page'] ?? ''));

// Handle logout immediately (no page file needed)
if ($page === 'logout') {
    logoutUser();
    setFlash('success', 'You have been logged out successfully.');
    header('Location: ' . BASE_URL . '/index.php?page=login');
    exit;
}

// Map page slugs to their PHP files
$pageMap = [
    'login'     => __DIR__ . '/pages/login.php',
    'register'  => __DIR__ . '/pages/register.php',
    'dashboard' => __DIR__ . '/pages/dashboard.php',
    'products'  => __DIR__ . '/pages/products.php',
    'users'     => __DIR__ . '/pages/users.php',
];

// Default page based on auth state
if ($page === '' || !isset($pageMap[$page])) {
    $page = isLoggedIn() ? 'dashboard' : 'login';
}

// Guest-only pages: redirect authenticated users away
$guestPages = ['login', 'register'];
if (in_array($page, $guestPages) && isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php?page=dashboard');
    exit;
}

// Serve the page
$pageFile = $pageMap[$page];
if (is_readable($pageFile)) {
    require $pageFile;
} else {
    http_response_code(404);
    echo '<h1>404 — Page not found</h1>';
}

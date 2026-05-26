<?php
/**
 * Authentication & Session Helpers
 * Inventory Management System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check whether the current visitor is logged in.
 * Redirects to login page if not authenticated.
 *
 * @return void
 */
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/index.php?page=login');
        exit;
    }
}

/**
 * Check whether the logged-in user has the 'admin' role.
 * Redirects to dashboard with an error flash if not.
 *
 * @return void
 */
function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        setFlash('error', 'Access denied. Administrator privileges required.');
        header('Location: ' . BASE_URL . '/index.php?page=dashboard');
        exit;
    }
}

/**
 * Return true if a user is currently logged in.
 *
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Return true if the logged-in user is an admin.
 *
 * @return bool
 */
function isAdmin(): bool {
    return ($_SESSION['user_role'] ?? '') === 'admin';
}

/**
 * Store a one-time flash message in the session.
 *
 * @param string $type    Message type: 'success' | 'error' | 'warning' | 'info'
 * @param string $message Human-readable message text
 * @return void
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Retrieve and clear any pending flash message from the session.
 *
 * @return array{type: string, message: string}|null
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Sanitize a string value for safe output in HTML.
 *
 * @param string $value Raw input
 * @return string        HTML-escaped string
 */
function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a CSRF token, storing it in the session if not yet created.
 *
 * @return string
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify that the submitted CSRF token matches the one stored in the session.
 *
 * @param string $token Token from the form submission
 * @return bool
 */
function verifyCsrf(string $token): bool {
    return isset($_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format a decimal number as Philippine Peso currency.
 *
 * @param float $amount
 * @return string  e.g. "₱1,234.56"
 */
function formatCurrency(float $amount): string {
    return '₱' . number_format($amount, 2);
}

/**
 * Format a timestamp into a human-readable date string.
 *
 * @param string $datetime MySQL datetime string
 * @return string           e.g. "Jan 01, 2025"
 */
function formatDate(string $datetime): string {
    return date('M d, Y', strtotime($datetime));
}

/**
 * Redirect to a relative URL path within the application.
 *
 * @param string $path  Relative path, e.g. 'index.php?page=dashboard'
 * @return void
 */
function redirect(string $path): void {
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

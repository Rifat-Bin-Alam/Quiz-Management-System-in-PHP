<?php
// includes/csrf.php - Cross-Site Request Forgery (CSRF) Protection

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate or retrieve CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output hidden CSRF token input field
 */
function csrf_field(): string {
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Verify CSRF token from POST request
 */
function verify_csrf(): bool {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Enforce CSRF token verification; dies or redirects if invalid
 */
function require_csrf(): void {
    if (!verify_csrf()) {
        http_response_code(403);
        if (function_exists('set_flash')) {
            set_flash('error', 'Security check failed (invalid or expired session token). Please submit the form again.');
        }
        // If there's a referrer on the same host, redirect back, otherwise exit
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (!empty($referer) && parse_url($referer, PHP_URL_HOST) === $_SERVER['HTTP_HOST']) {
            header("Location: " . $referer);
            exit();
        }
        die("Security validation failed (Invalid CSRF Token). Please return to the previous page and refresh.");
    }
}

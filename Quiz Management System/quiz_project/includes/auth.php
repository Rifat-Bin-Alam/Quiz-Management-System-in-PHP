<?php
// includes/auth.php - Session Management and Authentication Guards

if (session_status() === PHP_SESSION_NONE) {
    // Set secure cookie flags
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Check if current user is an authenticated Admin
 */
function is_admin(): bool {
    return isset($_SESSION['admin_email']) && !empty($_SESSION['admin_email']);
}

/**
 * Check if current user is an authenticated Teacher
 */
function is_teacher(): bool {
    return isset($_SESSION['teacher_id']) && !empty($_SESSION['teacher_id']);
}

/**
 * Check if current user is an authenticated Student
 */
function is_student(): bool {
    return isset($_SESSION['student_id']) && !empty($_SESSION['student_id']);
}

/**
 * Get current user role string
 */
function current_user_role(): string {
    if (is_admin()) return 'admin';
    if (is_teacher()) return 'teacher';
    if (is_student()) return 'student';
    return 'guest';
}

/**
 * Get current user display name
 */
function current_user_name(): string {
    if (is_admin()) {
        return $_SESSION['username'] ?? $_SESSION['admin_email'] ?? 'Administrator';
    }
    if (is_teacher()) {
        return $_SESSION['teacher_name'] ?? 'Teacher';
    }
    if (is_student()) {
        return $_SESSION['student_name'] ?? 'Student';
    }
    return 'Guest';
}

/**
 * Guard: Require Admin authentication
 */
function require_admin(): void {
    if (!is_admin()) {
        set_flash('error', 'Administrator authentication required to access this page.');
        header("Location: admin_login.php");
        exit();
    }
}

/**
 * Guard: Require Teacher authentication
 */
function require_teacher(): void {
    if (!is_teacher()) {
        set_flash('error', 'Teacher login required to access this page.');
        header("Location: teacher_login.php");
        exit();
    }
}

/**
 * Guard: Require Student authentication
 */
function require_student(): void {
    if (!is_student()) {
        set_flash('error', 'Student login required to access this page.');
        header("Location: student_login.php");
        exit();
    }
}

/**
 * Add a flash message to session
 */
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type, // 'success', 'error', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Retrieve and clear all queued flash messages
 */
function get_flash_messages(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Render flash messages as modern alerts
 */
function render_flash_messages(): string {
    $messages = get_flash_messages();
    if (empty($messages)) {
        return '';
    }

    $html = '<div class="flash-messages-container" aria-live="polite">';
    foreach ($messages as $msg) {
        $type = htmlspecialchars($msg['type'], ENT_QUOTES, 'UTF-8');
        $text = htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8');
        $icon = '';
        if ($type === 'success') {
            $icon = '<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>';
        } elseif ($type === 'error') {
            $icon = '<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>';
        } elseif ($type === 'warning') {
            $icon = '<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>';
        } else {
            $icon = '<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>';
        }

        $html .= '<div class="alert alert-' . $type . '" role="alert">';
        $html .= '<div class="alert-icon">' . $icon . '</div>';
        $html .= '<div class="alert-content">' . $text . '</div>';
        $html .= '<button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Close notification">&times;</button>';
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Check if demo credentials shortcuts are enabled
 * Enabled across both local development and cloud hosting (e.g. InfinityFree)
 */
function is_demo_enabled(): bool {
    // Enabled by default so demo autofill works on localhost and cloud hostings like InfinityFree
    if (defined('APP_ENV') && APP_ENV === 'disabled') {
        return false;
    }
    return true;
}

/**
 * Render compact demo credentials section with non-submitting autofill buttons
 */
function render_demo_credentials(array $accounts): string {
    if (!is_demo_enabled()) {
        return '';
    }

    $html = '<div class="demo-credentials-box" aria-label="Demo Login Credentials">';
    $html .= '<div class="demo-credentials-header">';
    $html .= '<div class="demo-credentials-title-group">';
    $html .= '<svg width="15" height="15" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>';
    $html .= '<span class="demo-credentials-title">Demo Login Credentials</span>';
    $html .= '</div>';
    $html .= '<span class="badge badge-info" style="font-size:0.7rem;padding:0.15rem 0.45rem;">Quick Access</span>';
    $html .= '</div>';
    $html .= '<div class="demo-credentials-list">';

    foreach ($accounts as $acc) {
        $email = htmlspecialchars($acc['email'], ENT_QUOTES, 'UTF-8');
        $pass = htmlspecialchars($acc['password'], ENT_QUOTES, 'UTF-8');
        $html .= '<div class="demo-credentials-item">';
        $html .= '<div class="demo-credentials-info">';
        $html .= '<span class="demo-email">' . $email . '</span>';
        $html .= '<span class="demo-pass">Password: <code>' . $pass . '</code></span>';
        $html .= '</div>';
        $html .= '<button type="button" class="btn btn-outline btn-sm demo-autofill-btn" data-email="' . $email . '" data-password="' . $pass . '" onclick="autofillLogin(\'' . $email . '\', \'' . $pass . '\')" title="Autofill ' . $email . '">';
        $html .= 'Autofill';
        $html .= '</button>';
        $html .= '</div>';
    }

    $html .= '</div>';
    $html .= '<div class="demo-credentials-note">Click Autofill to populate email and password instantly without submitting.</div>';
    $html .= '</div>';

    // Self-contained inline autofill helper ensuring 100% reliability on any hosting (InfinityFree, etc.)
    $html .= '<script>';
    $html .= 'function autofillLogin(email, password) {';
    $html .= '    var emailField = document.getElementById("email");';
    $html .= '    var passField = document.getElementById("password");';
    $html .= '    if (emailField) {';
    $html .= '        emailField.value = email;';
    $html .= '        emailField.dispatchEvent(new Event("input", { bubbles: true }));';
    $html .= '        emailField.focus();';
    $html .= '    }';
    $html .= '    if (passField) {';
    $html .= '        passField.value = password;';
    $html .= '        passField.dispatchEvent(new Event("input", { bubbles: true }));';
    $html .= '    }';
    $html .= '}';
    $html .= '</script>';

    return $html;
}


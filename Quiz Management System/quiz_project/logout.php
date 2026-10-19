<?php
// logout.php - Universal secure session termination
require_once __DIR__ . '/includes/auth.php';

// Unset all session variables
$_SESSION = [];

// Delete session cookie if active
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Restart clean session just for the flash message
session_start();
session_regenerate_id(true);
set_flash('info', 'You have been successfully signed out.');

header("Location: index.php");
exit();

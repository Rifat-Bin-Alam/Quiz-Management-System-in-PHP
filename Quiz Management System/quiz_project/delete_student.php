<?php
// delete_student.php - Server-Side Protection: Student Deletion Restricted
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';

// 1. Enforce Administrator authentication
require_admin();

// 2. Server-side enforcement: Admin deletion of records is permanently disabled
http_response_code(403);
set_flash('error', 'Action Denied: Student deletion is disabled for security.');

// Ensure no deletion queries are executed
header("Location: admin_dashboard.php");
exit();

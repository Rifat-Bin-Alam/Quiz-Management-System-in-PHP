<?php
// approve_teacher.php - Secure Teacher Approval Endpoint (Admin Only)
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

// 1. Enforce Administrator authentication
require_admin();

// 2. Enforce POST request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    set_flash('error', 'Invalid request method. Approval actions must be sent via POST.');
    header("Location: admin_dashboard.php");
    exit();
}

// 3. Enforce CSRF token verification
require_csrf();

// 4. Validate teacher ID as a positive integer
$teacher_id_raw = $_POST['teacher_id'] ?? $_POST['id'] ?? null;
$teacher_id = validate_id($teacher_id_raw);

if (!$teacher_id) {
    set_flash('error', 'Invalid teacher identification provided.');
    header("Location: admin_dashboard.php");
    exit();
}

// 5. Check if teacher exists and current approval status using prepared statements
$stmt = $conn->prepare("SELECT teacher_id, t_name, email, status FROM teacher WHERE teacher_id = ? LIMIT 1");
if (!$stmt) {
    set_flash('error', 'Database query error during status check.');
    header("Location: admin_dashboard.php");
    exit();
}

$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $stmt->close();
    set_flash('error', 'Teacher account not found.');
    header("Location: admin_dashboard.php");
    exit();
}

$teacher = $res->fetch_assoc();
$stmt->close();

if ($teacher['status'] === 'approved') {
    set_flash('info', 'Teacher "' . $teacher['t_name'] . '" is already approved.');
    header("Location: admin_dashboard.php");
    exit();
}

// 6. Update only the intended teacher record to 'approved'
$update_stmt = $conn->prepare("UPDATE teacher SET status = 'approved' WHERE teacher_id = ?");
if (!$update_stmt) {
    set_flash('error', 'Database query error during record update.');
    header("Location: admin_dashboard.php");
    exit();
}

$update_stmt->bind_param("i", $teacher_id);
if ($update_stmt->execute()) {
    set_flash('success', 'Teacher "' . $teacher['t_name'] . '" (' . $teacher['email'] . ') has been successfully approved.');
} else {
    set_flash('error', 'Failed to approve teacher record. Please try again.');
}

$update_stmt->close();
header("Location: admin_dashboard.php");
exit();

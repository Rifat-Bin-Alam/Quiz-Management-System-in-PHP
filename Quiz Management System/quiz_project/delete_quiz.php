<?php
// delete_quiz.php - Delete Quiz (Teacher Owned Only / Admin Blocked)
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

// Enforce Admin action block policy: Admin deletion is disabled
block_admin_action('Quiz deletion');

// Require Teacher authentication
require_teacher();

// Require POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    set_flash('error', 'Invalid deletion request method. Actions must be sent via POST.');
    header("Location: teacher_dashboard.php");
    exit();
}

// Enforce CSRF token verification
require_csrf();

$teacher_id = (int)$_SESSION['teacher_id'];
$quiz_id_raw = $_POST['quiz_id'] ?? $_GET['qid'] ?? null;
$quiz_id = validate_id($quiz_id_raw);

if (!$quiz_id) {
    set_flash('error', 'Invalid quiz identification.');
    header("Location: teacher_dashboard.php");
    exit();
}

// IDOR Prevention: Verify teacher owns this quiz
$check_stmt = $conn->prepare("SELECT quiz_id, quiz_name FROM quiz WHERE quiz_id = ? AND teacher_id = ? LIMIT 1");
if (!$check_stmt) {
    die("Database query error.");
}
$check_stmt->bind_param("ii", $quiz_id, $teacher_id);
$check_stmt->execute();
$check_res = $check_stmt->get_result();

if ($check_res->num_rows === 0) {
    $check_stmt->close();
    set_flash('error', 'Access denied: Quiz not found or does not belong to your account.');
    header("Location: teacher_dashboard.php");
    exit();
}

$quiz = $check_res->fetch_assoc();
$check_stmt->close();

// Begin Transaction for referential integrity
$conn->begin_transaction();

try {
    // 1. Delete associated student scores
    $del_score = $conn->prepare("DELETE FROM score WHERE quiz_id = ?");
    $del_score->bind_param("i", $quiz_id);
    $del_score->execute();
    $del_score->close();

    // 2. Delete associated questions
    $del_qn = $conn->prepare("DELETE FROM question WHERE quiz_id = ?");
    $del_qn->bind_param("i", $quiz_id);
    $del_qn->execute();
    $del_qn->close();

    // 3. Delete the quiz itself
    $del_quiz = $conn->prepare("DELETE FROM quiz WHERE quiz_id = ? AND teacher_id = ?");
    $del_quiz->bind_param("ii", $quiz_id, $teacher_id);
    $del_quiz->execute();
    $del_quiz->close();

    $conn->commit();
    set_flash('success', 'Quiz "' . $quiz['quiz_name'] . '" and all related records were successfully deleted.');
} catch (Exception $e) {
    $conn->rollback();
    set_flash('error', 'Failed to delete quiz due to a database error. Changes rolled back.');
}

header("Location: teacher_dashboard.php");
exit();

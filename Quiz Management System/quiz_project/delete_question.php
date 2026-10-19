<?php
// delete_question.php - Delete Question (Teacher Owned Only / Admin Blocked)
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

// Enforce Admin action block policy: Admin deletion is disabled
block_admin_action('Question deletion');

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
$question_id_raw = $_POST['question_id'] ?? $_GET['id'] ?? null;
$question_id = validate_id($question_id_raw);

if (!$question_id) {
    set_flash('error', 'Invalid question identification.');
    header("Location: teacher_dashboard.php");
    exit();
}

// IDOR Prevention: Verify teacher owns the quiz for this question
$check_stmt = $conn->prepare("SELECT qn.question_id, qn.quiz_id, q.teacher_id 
                              FROM question qn 
                              JOIN quiz q ON qn.quiz_id = q.quiz_id 
                              WHERE qn.question_id = ? AND q.teacher_id = ? 
                              LIMIT 1");
if (!$check_stmt) {
    die("Database query error.");
}
$check_stmt->bind_param("ii", $question_id, $teacher_id);
$check_stmt->execute();
$check_res = $check_stmt->get_result();

if ($check_res->num_rows === 0) {
    $check_stmt->close();
    set_flash('error', 'Access denied: Question not found or does not belong to your course quiz.');
    header("Location: teacher_dashboard.php");
    exit();
}

$row = $check_res->fetch_assoc();
$quiz_id = (int)$row['quiz_id'];
$check_stmt->close();

// Delete the question
$del_stmt = $conn->prepare("DELETE FROM question WHERE question_id = ?");
if ($del_stmt) {
    $del_stmt->bind_param("i", $question_id);
    if ($del_stmt->execute()) {
        set_flash('success', 'Question deleted successfully.');
    } else {
        set_flash('error', 'Failed to delete question. Please try again.');
    }
    $del_stmt->close();
}

header("Location: view_quiz.php?qid=" . $quiz_id);
exit();

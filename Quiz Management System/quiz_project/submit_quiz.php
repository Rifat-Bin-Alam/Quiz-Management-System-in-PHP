<?php
// submit_quiz.php - Authoritative Server-Side Evaluation & Score Recording
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

// 1. Enforce Student authentication
require_student();

// 2. Enforce POST request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    set_flash('error', 'Invalid request method. Quiz submissions must use POST.');
    header("Location: take_quiz.php");
    exit();
}

// 3. Enforce CSRF token verification
require_csrf();

$student_id = (int)$_SESSION['student_id'];
$quiz_id_raw = $_POST['quiz_id'] ?? null;
$quiz_id = validate_id($quiz_id_raw);

if (!$quiz_id) {
    set_flash('error', 'Invalid examination identifier.');
    header("Location: take_quiz.php");
    exit();
}

// 4. Fetch quiz details to ensure quiz exists
$q_stmt = $conn->prepare("SELECT quiz_name FROM quiz WHERE quiz_id = ? LIMIT 1");
if (!$q_stmt) {
    die("Database query error.");
}
$q_stmt->bind_param("i", $quiz_id);
$q_stmt->execute();
$q_res = $q_stmt->get_result();

if ($q_res->num_rows === 0) {
    $q_stmt->close();
    set_flash('error', 'Examination not found.');
    header("Location: take_quiz.php");
    exit();
}

$quiz_name = $q_res->fetch_assoc()['quiz_name'];
$q_stmt->close();

// 5. Authoritative Server-Side Question Retrieval
// Retrieve ALL questions and their correct answers directly from the database for this quiz
$db_questions = [];
$qn_stmt = $conn->prepare("SELECT question_id, correct_answer FROM question WHERE quiz_id = ?");
if ($qn_stmt) {
    $qn_stmt->bind_param("i", $quiz_id);
    $qn_stmt->execute();
    $res = $qn_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $db_questions[(int)$row['question_id']] = $row['correct_answer'];
    }
    $qn_stmt->close();
}

$total_questions = count($db_questions);

if ($total_questions === 0) {
    set_flash('error', 'Cannot grade examination with zero registered questions.');
    header("Location: take_quiz.php");
    exit();
}

// 6. Calculate Score Server-Side
$submitted_answers = $_POST['answers'] ?? [];
if (!is_array($submitted_answers)) {
    $submitted_answers = [];
}

$correct_count = 0;

foreach ($db_questions as $question_id => $correct_answer) {
    if (isset($submitted_answers[$question_id])) {
        $student_choice = trim((string)$submitted_answers[$question_id]);
        if ($student_choice === $correct_answer) {
            $correct_count++;
        }
    }
}

// 7. Insert Score Record
$score_stmt = $conn->prepare("INSERT INTO score (student_id, quiz_id, score_point, total_point) VALUES (?, ?, ?, ?)");
if ($score_stmt) {
    $score_stmt->bind_param("iiii", $student_id, $quiz_id, $correct_count, $total_questions);
    if ($score_stmt->execute()) {
        $percentage = round(($correct_count / $total_questions) * 100, 1);
        set_flash('success', "Examination '{$quiz_name}' submitted successfully! You achieved {$correct_count} / {$total_questions} ({$percentage}%).");
        $score_stmt->close();
        header("Location: view_score.php");
        exit();
    } else {
        set_flash('error', 'Failed to record examination score. Please contact your instructor.');
    }
    $score_stmt->close();
} else {
    set_flash('error', 'Database service error during grading.');
}

header("Location: student_dashboard.php");
exit();

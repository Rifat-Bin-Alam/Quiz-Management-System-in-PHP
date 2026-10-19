<?php
// add_question.php - Author Question for Quiz (Teacher Only)
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

// Enforce Admin action block policy: Admin creation is disabled
block_admin_action('Question creation');

// Require Teacher authentication
require_teacher();

$teacher_id = (int)$_SESSION['teacher_id'];
$quiz_id = validate_id($_GET['qid'] ?? $_POST['quiz_id'] ?? null);

if (!$quiz_id) {
    set_flash('error', 'Please select a valid quiz to add questions.');
    header("Location: teacher_dashboard.php");
    exit();
}

// IDOR Prevention: Verify quiz belongs to the logged-in teacher
$q_stmt = $conn->prepare("SELECT quiz_id, quiz_name FROM quiz WHERE quiz_id = ? AND teacher_id = ? LIMIT 1");
if (!$q_stmt) {
    die("Database query error.");
}
$q_stmt->bind_param("ii", $quiz_id, $teacher_id);
$q_stmt->execute();
$quiz_res = $q_stmt->get_result();

if ($quiz_res->num_rows === 0) {
    $q_stmt->close();
    set_flash('error', 'Access denied: Quiz not found or does not belong to your account.');
    header("Location: teacher_dashboard.php");
    exit();
}

$quiz = $quiz_res->fetch_assoc();
$q_stmt->close();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $question_text = trim($_POST['question_text'] ?? '');
    $option1 = trim($_POST['option1'] ?? '');
    $option2 = trim($_POST['option2'] ?? '');
    $option3 = trim($_POST['option3'] ?? '');
    $correct_choice = trim($_POST['correct_choice'] ?? '');

    if (empty($question_text) || empty($option1) || empty($option2) || empty($option3)) {
        $error = "Please fill in the question text and all three response options.";
    } elseif (!in_array($correct_choice, ['1', '2', '3'], true)) {
        $error = "Please select which option represents the correct answer.";
    } else {
        // Resolve correct answer value based on selected radio button
        $correct_answer = match ($correct_choice) {
            '1' => $option1,
            '2' => $option2,
            '3' => $option3,
            default => ''
        };

        $stmt = $conn->prepare("INSERT INTO question (quiz_id, question_text, option1, option2, option3, correct_answer) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isssss", $quiz_id, $question_text, $option1, $option2, $option3, $correct_answer);
            if ($stmt->execute()) {
                set_flash('success', 'Question added successfully to "' . $quiz['quiz_name'] . '".');
                header("Location: view_quiz.php?qid=" . $quiz_id);
                exit();
            } else {
                $error = "Failed to add question to database. Please try again.";
            }
            $stmt->close();
        } else {
            $error = "Database service error.";
        }
    }
}

$page_title = 'Add Question — ' . $quiz['quiz_name'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Add Question</h1>
        <p class="page-subtitle">Quiz: <strong><?php echo e($quiz['quiz_name']); ?></strong> (ID #<?php echo e($quiz['quiz_id']); ?>)</p>
    </div>
    <div class="page-actions">
        <a href="view_quiz.php?qid=<?php echo e($quiz_id); ?>" class="btn btn-outline">&larr; Back to Quiz Questions</a>
    </div>
</div>

<div class="card" style="max-width: 720px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Question Builder</h2>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert">
                <div class="alert-icon">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                </div>
                <div class="alert-content"><?php echo e($error); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="add_question.php?qid=<?php echo e($quiz_id); ?>" novalidate>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="quiz_id" value="<?php echo e($quiz_id); ?>">

            <div class="form-group">
                <label class="form-label" for="question_text">Question Prompt</label>
                <textarea id="question_text" name="question_text" class="form-control" rows="3" placeholder="Enter the examination question or problem statement..." required autofocus><?php echo e($_POST['question_text'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Response Options &amp; Correct Answer Key</label>
                <p style="font-size:0.85rem;color:var(--slate-500);margin-bottom:0.75rem;">Enter the text for each of the 3 options and mark the radio button for the correct choice.</p>

                <div style="display:flex;flex-direction:column;gap:0.75rem;">
                    <!-- Option 1 -->
                    <div class="option-builder-row">
                        <label class="option-builder-selector" for="correct_1">
                            <input type="radio" name="correct_choice" value="1" id="correct_1" <?php echo (($_POST['correct_choice'] ?? '1') === '1') ? 'checked' : ''; ?> style="width:1.2rem;height:1.2rem;accent-color:var(--emerald-600);" required>
                            <span>Option 1:</span>
                        </label>
                        <input type="text" name="option1" class="form-control" placeholder="Answer option A" value="<?php echo e($_POST['option1'] ?? ''); ?>" required>
                    </div>

                    <!-- Option 2 -->
                    <div class="option-builder-row">
                        <label class="option-builder-selector" for="correct_2">
                            <input type="radio" name="correct_choice" value="2" id="correct_2" <?php echo (($_POST['correct_choice'] ?? '') === '2') ? 'checked' : ''; ?> style="width:1.2rem;height:1.2rem;accent-color:var(--emerald-600);">
                            <span>Option 2:</span>
                        </label>
                        <input type="text" name="option2" class="form-control" placeholder="Answer option B" value="<?php echo e($_POST['option2'] ?? ''); ?>" required>
                    </div>

                    <!-- Option 3 -->
                    <div class="option-builder-row">
                        <label class="option-builder-selector" for="correct_3">
                            <input type="radio" name="correct_choice" value="3" id="correct_3" <?php echo (($_POST['correct_choice'] ?? '') === '3') ? 'checked' : ''; ?> style="width:1.2rem;height:1.2rem;accent-color:var(--emerald-600);">
                            <span>Option 3:</span>
                        </label>
                        <input type="text" name="option3" class="form-control" placeholder="Answer option C" value="<?php echo e($_POST['option3'] ?? ''); ?>" required>
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:2rem;">
                <a href="view_quiz.php?qid=<?php echo e($quiz_id); ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    Save Question &rarr;
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

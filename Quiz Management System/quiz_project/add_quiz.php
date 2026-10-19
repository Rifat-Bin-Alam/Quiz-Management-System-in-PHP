<?php
// add_quiz.php - Create New Quiz (Teacher Only)
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

// Enforce Admin action block policy: Admin creation is disabled
block_admin_action('Quiz creation');

// Require Teacher authentication
require_teacher();

$teacher_id = (int)$_SESSION['teacher_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $quiz_name = trim($_POST['quiz_name'] ?? '');

    if (empty($quiz_name)) {
        $error = "Please enter a descriptive quiz title.";
    } elseif (strlen($quiz_name) > 200) {
        $error = "Quiz title cannot exceed 200 characters.";
    } else {
        $stmt = $conn->prepare("INSERT INTO quiz (teacher_id, quiz_name) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("is", $teacher_id, $quiz_name);
            if ($stmt->execute()) {
                $new_quiz_id = $conn->insert_id;
                $stmt->close();
                set_flash('success', 'Quiz "' . $quiz_name . '" created successfully! You can now add examination questions.');
                header("Location: add_question.php?qid=" . $new_quiz_id);
                exit();
            } else {
                $error = "Failed to create quiz. Please try again.";
            }
            $stmt->close();
        } else {
            $error = "Database service error.";
        }
    }
}

$page_title = 'Create New Quiz';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Create New Examination</h1>
        <p class="page-subtitle">Define the course assessment title before authoring questions</p>
    </div>
    <div class="page-actions">
        <a href="teacher_dashboard.php" class="btn btn-outline">&larr; Back to Quizzes</a>
    </div>
</div>

<div class="card" style="max-width: 640px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Quiz Details</h2>
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

        <form method="POST" action="add_quiz.php" novalidate>
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label class="form-label" for="quiz_name">Quiz Title / Course Subject</label>
                <input type="text" id="quiz_name" name="quiz_name" class="form-control" placeholder="e.g. CSC 387 — Advanced Web Security Midterm" value="<?php echo e($_POST['quiz_name'] ?? ''); ?>" required autofocus>
                <small style="color:var(--slate-500);display:block;margin-top:0.35rem;">Enter a clear title representing the subject, topic, or examination.</small>
            </div>

            <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1.5rem;">
                <a href="teacher_dashboard.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    Create &amp; Add Questions &rarr;
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

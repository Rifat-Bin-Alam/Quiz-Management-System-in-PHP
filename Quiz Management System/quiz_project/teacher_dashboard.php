<?php
// teacher_dashboard.php - Instructor Dashboard & Assessment Management
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

// Require Teacher authentication
require_teacher();

$teacher_id = (int)$_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'];

// Fetch teacher metrics
$total_quizzes = 0;
$total_questions = 0;
$total_submissions = 0;

$m_stmt = $conn->prepare("SELECT 
    COUNT(DISTINCT q.quiz_id) AS total_quizzes,
    COUNT(DISTINCT qn.question_id) AS total_questions,
    COUNT(DISTINCT s.score_id) AS total_submissions
    FROM quiz q
    LEFT JOIN question qn ON q.quiz_id = qn.quiz_id
    LEFT JOIN score s ON q.quiz_id = s.quiz_id
    WHERE q.teacher_id = ?");
if ($m_stmt) {
    $m_stmt->bind_param("i", $teacher_id);
    $m_stmt->execute();
    $m_res = $m_stmt->get_result()->fetch_assoc();
    $total_quizzes = (int)($m_res['total_quizzes'] ?? 0);
    $total_questions = (int)($m_res['total_questions'] ?? 0);
    $total_submissions = (int)($m_res['total_submissions'] ?? 0);
    $m_stmt->close();
}

// Fetch quizzes authored by this teacher
$quizzes = [];
$q_stmt = $conn->prepare("SELECT q.quiz_id, q.quiz_name, q.date_created,
    (SELECT COUNT(*) FROM question WHERE quiz_id = q.quiz_id) AS question_count,
    (SELECT COUNT(*) FROM score WHERE quiz_id = q.quiz_id) AS attempt_count
    FROM quiz q
    WHERE q.teacher_id = ?
    ORDER BY q.date_created DESC");
if ($q_stmt) {
    $q_stmt->bind_param("i", $teacher_id);
    $q_stmt->execute();
    $res = $q_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $quizzes[] = $row;
    }
    $q_stmt->close();
}

$page_title = 'Teacher Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Instructor Assessment Portal</h1>
        <p class="page-subtitle">Welcome, <?php echo e($teacher_name); ?>. Create quizzes, author questions, and review student grades.</p>
    </div>
    <div class="page-actions">
        <a href="add_quiz.php" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Create New Quiz
        </a>
    </div>
</div>

<!-- Metrics Summary -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">My Quizzes</div>
        <div class="stat-value"><?php echo $total_quizzes; ?></div>
        <div class="stat-subtext">Active course assessments</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Total Questions</div>
        <div class="stat-value"><?php echo $total_questions; ?></div>
        <div class="stat-subtext">Authored across all quizzes</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Student Submissions</div>
        <div class="stat-value"><?php echo $total_submissions; ?></div>
        <div class="stat-subtext">Total evaluated attempts</div>
    </div>
</div>

<!-- Quizzes Table Card -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">My Created Quizzes</h2>
        <span style="font-size:0.85rem;color:var(--slate-500);"><?php echo count($quizzes); ?> examination(s) published</span>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:70px;">ID</th>
                        <th>Quiz Name</th>
                        <th>Date Published</th>
                        <th>Questions</th>
                        <th>Attempts</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($quizzes)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:2.5rem;color:var(--slate-400);">
                                <p style="font-size:1.05rem;font-weight:500;margin-bottom:0.5rem;">No quizzes created yet</p>
                                <p style="font-size:0.875rem;margin-bottom:1rem;">Get started by publishing your first academic quiz.</p>
                                <a href="add_quiz.php" class="btn btn-primary btn-sm">Create First Quiz</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($quizzes as $quiz): ?>
                            <tr>
                                <td><code>#<?php echo e($quiz['quiz_id']); ?></code></td>
                                <td><strong><?php echo e($quiz['quiz_name']); ?></strong></td>
                                <td><?php echo e($quiz['date_created']); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo e($quiz['question_count']); ?> Questions
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo ($quiz['attempt_count'] > 0) ? 'badge-success' : 'badge-pending'; ?>">
                                        <?php echo e($quiz['attempt_count']); ?> Submissions
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex;gap:0.35rem;align-items:center;">
                                        <a href="view_quiz.php?qid=<?php echo e($quiz['quiz_id']); ?>" class="btn btn-outline btn-sm">
                                            Questions
                                        </a>

                                        <a href="add_question.php?qid=<?php echo e($quiz['quiz_id']); ?>" class="btn btn-subtle btn-sm">
                                            + Question
                                        </a>

                                        <a href="view_results.php?qid=<?php echo e($quiz['quiz_id']); ?>" class="btn btn-outline btn-sm">
                                            Results
                                        </a>

                                        <!-- Secure POST deletion with CSRF token -->
                                        <form method="POST" action="delete_quiz.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete quiz \'<?php echo e($quiz['quiz_name']); ?>\' and all associated questions and student scores?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="quiz_id" value="<?php echo e($quiz['quiz_id']); ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete Quiz">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

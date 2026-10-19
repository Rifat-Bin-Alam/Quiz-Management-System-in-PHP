<?php
// view_quiz.php - Quiz Question Catalog (Teacher Management / Admin Read-Only)
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

// Access Control: Must be either Admin or Teacher
if (!is_admin() && !is_teacher()) {
    set_flash('error', 'Authentication required to view quiz questions.');
    header("Location: index.php");
    exit();
}

$quiz_id = validate_id($_GET['qid'] ?? null);

if (!$quiz_id) {
    set_flash('error', 'Invalid quiz identifier.');
    if (is_admin()) {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: teacher_dashboard.php");
    }
    exit();
}

// Fetch quiz info
$q_stmt = $conn->prepare("SELECT q.quiz_id, q.quiz_name, q.teacher_id, q.date_created, t.t_name 
                          FROM quiz q 
                          LEFT JOIN teacher t ON q.teacher_id = t.teacher_id 
                          WHERE q.quiz_id = ? LIMIT 1");
if (!$q_stmt) {
    die("Database error.");
}
$q_stmt->bind_param("i", $quiz_id);
$q_stmt->execute();
$q_res = $q_stmt->get_result();

if ($q_res->num_rows === 0) {
    $q_stmt->close();
    set_flash('error', 'Quiz examination not found.');
    header(is_admin() ? "Location: admin_dashboard.php" : "Location: teacher_dashboard.php");
    exit();
}

$quiz = $q_res->fetch_assoc();
$q_stmt->close();

// IDOR Check: If logged in as Teacher, ensure ownership
$is_owner_teacher = false;
if (is_teacher()) {
    if ((int)$quiz['teacher_id'] !== (int)$_SESSION['teacher_id']) {
        set_flash('error', 'Access denied: You do not have permission to view or manage questions for this quiz.');
        header("Location: teacher_dashboard.php");
        exit();
    }
    $is_owner_teacher = true;
}

// Fetch questions for this quiz
$questions = [];
$qn_stmt = $conn->prepare("SELECT question_id, question_text, option1, option2, option3, correct_answer 
                           FROM question 
                           WHERE quiz_id = ? 
                           ORDER BY question_id ASC");
if ($qn_stmt) {
    $qn_stmt->bind_param("i", $quiz_id);
    $qn_stmt->execute();
    $res = $qn_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $questions[] = $row;
    }
    $qn_stmt->close();
}

$page_title = 'Questions — ' . $quiz['quiz_name'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e($quiz['quiz_name']); ?></h1>
        <p class="page-subtitle">
            Course Instructor: <strong><?php echo e($quiz['t_name'] ?? 'Unassigned'); ?></strong> &bull; Published: <?php echo e($quiz['date_created']); ?>
            <?php if (is_admin()): ?>
                &bull; <span class="badge badge-info">Admin Read-Only Inspection</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="page-actions">
        <?php if ($is_owner_teacher): ?>
            <a href="add_question.php?qid=<?php echo e($quiz_id); ?>" class="btn btn-primary">
                + Add Question
            </a>
            <a href="teacher_dashboard.php" class="btn btn-outline">&larr; Back to Dashboard</a>
        <?php else: ?>
            <!-- Admin View: Creation is disabled -->
            <div class="action-disabled-group">
                <button type="button" class="btn btn-primary btn-disabled" disabled aria-disabled="true" title="Creation is currently disabled">
                    + Add Question
                </button>
                <span class="badge-disabled">Creation is currently disabled</span>
            </div>
            <a href="admin_dashboard.php" class="btn btn-outline">&larr; Back to Admin Oversight</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Question Catalog</h2>
        <span style="font-size:0.85rem;color:var(--slate-500);"><?php echo count($questions); ?> question(s) configured</span>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>Question Statement</th>
                        <th>Option 1</th>
                        <th>Option 2</th>
                        <th>Option 3</th>
                        <th>Correct Answer Key</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($questions)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:2.5rem;color:var(--slate-400);">
                                <p style="font-size:1.05rem;font-weight:500;margin-bottom:0.5rem;">No questions added yet</p>
                                <?php if ($is_owner_teacher): ?>
                                    <p style="font-size:0.875rem;margin-bottom:1rem;">Add questions to make this quiz ready for candidates.</p>
                                    <a href="add_question.php?qid=<?php echo e($quiz_id); ?>" class="btn btn-primary btn-sm">+ Add First Question</a>
                                <?php else: ?>
                                    <p style="font-size:0.875rem;">This quiz does not have any questions registered yet.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $num = 1; foreach ($questions as $qn): ?>
                            <tr>
                                <td><code><?php echo $num++; ?></code></td>
                                <td style="max-width:320px;"><strong><?php echo e($qn['question_text']); ?></strong></td>
                                <td><?php echo e($qn['option1']); ?></td>
                                <td><?php echo e($qn['option2']); ?></td>
                                <td><?php echo e($qn['option3']); ?></td>
                                <td>
                                    <span class="badge badge-success">
                                        <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        <?php echo e($qn['correct_answer']); ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <?php if ($is_owner_teacher): ?>
                                        <div style="display:inline-flex;gap:0.35rem;align-items:center;">
                                            <a href="edit_question.php?id=<?php echo e($qn['question_id']); ?>" class="btn btn-outline btn-sm">
                                                Edit
                                            </a>

                                            <!-- Teacher Question Delete via POST + CSRF -->
                                            <form method="POST" action="delete_question.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this question?');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="question_id" value="<?php echo e($qn['question_id']); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <!-- Admin View: Editing and Deletion disabled -->
                                        <div style="display:inline-flex;gap:0.35rem;align-items:center;">
                                            <div class="action-disabled-group">
                                                <button type="button" class="btn btn-outline btn-sm btn-disabled" disabled aria-disabled="true" title="Editing is currently disabled">
                                                    Edit
                                                </button>
                                                <span class="badge-disabled">Editing is currently disabled</span>
                                            </div>

                                            <div class="action-disabled-group">
                                                <button type="button" class="btn btn-danger btn-sm btn-disabled" disabled aria-disabled="true" title="Deletion is currently disabled">
                                                    Delete
                                                </button>
                                                <span class="badge-disabled">Deletion is currently disabled</span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
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

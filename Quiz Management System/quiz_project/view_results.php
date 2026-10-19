<?php
// view_results.php - Quiz Results & Feedback (Teacher Evaluation / Admin Read-Only)
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

// Access Control: Must be either Admin or Teacher
if (!is_admin() && !is_teacher()) {
    set_flash('error', 'Authentication required to view evaluation results.');
    header("Location: index.php");
    exit();
}

$quiz_id = validate_id($_GET['qid'] ?? $_POST['quiz_id'] ?? null);

if (!$quiz_id) {
    set_flash('error', 'Invalid quiz identifier.');
    header(is_admin() ? "Location: admin_dashboard.php" : "Location: teacher_dashboard.php");
    exit();
}

// Fetch quiz info
$q_stmt = $conn->prepare("SELECT q.quiz_id, q.quiz_name, q.teacher_id, q.date_created, t.t_name 
                          FROM quiz q 
                          LEFT JOIN teacher t ON q.teacher_id = t.teacher_id 
                          WHERE q.quiz_id = ? LIMIT 1");
if (!$q_stmt) {
    die("Database query error.");
}
$q_stmt->bind_param("i", $quiz_id);
$q_stmt->execute();
$q_res = $q_stmt->get_result();

if ($q_res->num_rows === 0) {
    $q_stmt->close();
    set_flash('error', 'Quiz not found.');
    header(is_admin() ? "Location: admin_dashboard.php" : "Location: teacher_dashboard.php");
    exit();
}

$quiz = $q_res->fetch_assoc();
$q_stmt->close();

// IDOR Check: If teacher, verify ownership
$is_owner_teacher = false;
if (is_teacher()) {
    if ((int)$quiz['teacher_id'] !== (int)$_SESSION['teacher_id']) {
        set_flash('error', 'Access denied: You do not have permission to view results for this examination.');
        header("Location: teacher_dashboard.php");
        exit();
    }
    $is_owner_teacher = true;
}

// Handle Feedback Submission (Permitted for course teacher only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    if (is_admin()) {
        block_admin_action('Score feedback modification');
    }

    require_csrf();

    $score_id = validate_id($_POST['score_id'] ?? null);
    $feedback = trim($_POST['feedback'] ?? '');

    if ($score_id && $is_owner_teacher) {
        // Verify score belongs to this teacher's quiz
        $verify_stmt = $conn->prepare("SELECT s.score_id FROM score s JOIN quiz q ON s.quiz_id = q.quiz_id WHERE s.score_id = ? AND q.teacher_id = ?");
        if ($verify_stmt) {
            $teacher_id = (int)$_SESSION['teacher_id'];
            $verify_stmt->bind_param("ii", $score_id, $teacher_id);
            $verify_stmt->execute();
            if ($verify_stmt->get_result()->num_rows > 0) {
                $up_stmt = $conn->prepare("UPDATE score SET feedback = ? WHERE score_id = ?");
                if ($up_stmt) {
                    $up_stmt->bind_param("si", $feedback, $score_id);
                    $up_stmt->execute();
                    $up_stmt->close();
                    set_flash('success', 'Student feedback successfully recorded.');
                }
            } else {
                set_flash('error', 'Access denied to update feedback on this record.');
            }
            $verify_stmt->close();
        }
    }

    header("Location: view_results.php?qid=" . $quiz_id);
    exit();
}

// Fetch all student submissions
$results = [];
$res_stmt = $conn->prepare("SELECT s.score_id, st.student_id, st.s_name, st.email, s.score_point, s.total_point, s.feedback, s.date_created 
                            FROM score s 
                            JOIN student st ON s.student_id = st.student_id 
                            WHERE s.quiz_id = ? 
                            ORDER BY s.score_id DESC");
if ($res_stmt) {
    $res_stmt->bind_param("i", $quiz_id);
    $res_stmt->execute();
    $res = $res_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $results[] = $row;
    }
    $res_stmt->close();
}

// Calculate summary stats
$total_submissions = count($results);
$avg_percentage = 0;
if ($total_submissions > 0) {
    $sum_pct = 0;
    foreach ($results as $r) {
        $tot = max(1, (int)$r['total_point']);
        $sum_pct += ((int)$r['score_point'] / $tot) * 100;
    }
    $avg_percentage = round($sum_pct / $total_submissions, 1);
}

$page_title = 'Results — ' . $quiz['quiz_name'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Candidate Evaluation &amp; Results</h1>
        <p class="page-subtitle">
            Quiz: <strong><?php echo e($quiz['quiz_name']); ?></strong> &bull; Instructor: <?php echo e($quiz['t_name'] ?? 'Unassigned'); ?>
            <?php if (is_admin()): ?>
                &bull; <span class="badge badge-info">Admin Read-Only Inspection</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="page-actions">
        <?php if ($is_owner_teacher): ?>
            <a href="teacher_dashboard.php" class="btn btn-outline">&larr; Back to Dashboard</a>
        <?php else: ?>
            <a href="admin_dashboard.php" class="btn btn-outline">&larr; Back to Admin Oversight</a>
        <?php endif; ?>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Submissions</div>
        <div class="stat-value"><?php echo $total_submissions; ?></div>
        <div class="stat-subtext">Completed evaluations</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Average Performance</div>
        <div class="stat-value"><?php echo $avg_percentage; ?>%</div>
        <div class="stat-subtext">Across all student attempts</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Student Submissions</h2>
        <span style="font-size:0.85rem;color:var(--slate-500);"><?php echo $total_submissions; ?> record(s) logged</span>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:70px;">ID</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Submission Time</th>
                        <th>Teacher Evaluation / Feedback</th>
                        <?php if ($is_owner_teacher): ?>
                            <th style="text-align:right;">Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)): ?>
                        <tr>
                            <td colspan="<?php echo $is_owner_teacher ? '8' : '7'; ?>" style="text-align:center;padding:2.5rem;color:var(--slate-400);">
                                No candidates have submitted attempts for this examination yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($results as $row): 
                            $tot = max(1, (int)$row['total_point']);
                            $pct = round(((int)$row['score_point'] / $tot) * 100, 1);
                            $badge_class = ($pct >= 70) ? 'badge-success' : (($pct >= 50) ? 'badge-pending' : 'badge-disabled');
                        ?>
                            <tr>
                                <td><code>#<?php echo e($row['student_id']); ?></code></td>
                                <td><strong><?php echo e($row['s_name']); ?></strong></td>
                                <td><?php echo e($row['email']); ?></td>
                                <td>
                                    <strong><?php echo e($row['score_point']); ?></strong> / <?php echo e($row['total_point']); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $pct; ?>%
                                    </span>
                                </td>
                                <td><?php echo e($row['date_created']); ?></td>
                                <td>
                                    <?php if ($is_owner_teacher): ?>
                                        <form method="POST" action="view_results.php?qid=<?php echo e($quiz_id); ?>" style="display:flex;gap:0.4rem;align-items:center;min-width:220px;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="quiz_id" value="<?php echo e($quiz_id); ?>">
                                            <input type="hidden" name="score_id" value="<?php echo e($row['score_id']); ?>">
                                            <input type="text" name="feedback" class="form-control" style="padding:0.4rem 0.65rem;font-size:0.9rem;min-height:38px;" placeholder="e.g. Good progress / Review topic 3" value="<?php echo e($row['feedback'] ?? ''); ?>">
                                            <button type="submit" name="submit_feedback" class="btn btn-primary btn-sm" style="flex-shrink:0;">Save</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size:0.875rem;color:var(--slate-700);">
                                            <?php echo !empty($row['feedback']) ? e($row['feedback']) : '<em>No feedback provided</em>'; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($is_owner_teacher): ?>
                                    <td style="text-align:right;">
                                        <span class="badge badge-info">Logged</span>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

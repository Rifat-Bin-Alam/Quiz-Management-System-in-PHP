<?php
// view_score.php - Student Historical Performance & Feedback Report
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

// Require Student authentication
require_student();

$student_id = (int)$_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

// Fetch scores using prepared statement
$scores = [];
$sql = "SELECT s.score_id, s.score_point, s.total_point, s.feedback, s.date_created,
        q.quiz_id, q.quiz_name, t.t_name
        FROM score s
        JOIN quiz q ON s.quiz_id = q.quiz_id
        LEFT JOIN teacher t ON q.teacher_id = t.teacher_id
        WHERE s.student_id = ?
        ORDER BY s.score_id DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $scores[] = $row;
    }
    $stmt->close();
}

// Compute statistics
$total_attempts = count($scores);
$passed_count = 0;
$total_pct_sum = 0;

foreach ($scores as $s) {
    $tot = max(1, (int)$s['total_point']);
    $pct = ((int)$s['score_point'] / $tot) * 100;
    $total_pct_sum += $pct;
    if ($pct >= 50.0) {
        $passed_count++;
    }
}

$avg_pct = ($total_attempts > 0) ? round($total_pct_sum / $total_attempts, 1) : 0;

$page_title = 'My Examination Scores';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Candidate Academic Transcript</h1>
        <p class="page-subtitle">Historical records of examination attempts, percentage rankings, and teacher evaluations</p>
    </div>
    <div class="page-actions">
        <a href="take_quiz.php" class="btn btn-primary">+ Take Another Quiz</a>
        <a href="student_dashboard.php" class="btn btn-outline">&larr; Back to Dashboard</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Completed</div>
        <div class="stat-value"><?php echo $total_attempts; ?></div>
        <div class="stat-subtext">Graded attempts logged</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Average Score</div>
        <div class="stat-value"><?php echo $avg_pct; ?>%</div>
        <div class="stat-subtext">Cumulative average grade</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Pass Rate</div>
        <div class="stat-value" style="color:var(--emerald-600);"><?php echo $passed_count; ?> / <?php echo $total_attempts; ?></div>
        <div class="stat-subtext">Assessments scoring &ge; 50%</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Completed Examination History</h2>
        <span style="font-size:0.85rem;color:var(--slate-500);"><?php echo $total_attempts; ?> attempt(s) recorded</span>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:70px;">Attempt</th>
                        <th>Examination Title</th>
                        <th>Instructor</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Teacher Evaluation &amp; Feedback</th>
                        <th>Submission Date</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($scores)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center;padding:2.5rem;color:var(--slate-400);">
                                <p style="font-size:1.05rem;font-weight:500;margin-bottom:0.5rem;">No examination records logged yet</p>
                                <p style="font-size:0.875rem;margin-bottom:1rem;">Select a quiz from the catalog to take your first test.</p>
                                <a href="take_quiz.php" class="btn btn-primary btn-sm">Explore Quizzes</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($scores as $s): 
                            $tot = max(1, (int)$s['total_point']);
                            $pct = round(((int)$s['score_point'] / $tot) * 100, 1);
                            $badge_class = ($pct >= 70) ? 'badge-success' : (($pct >= 50) ? 'badge-pending' : 'badge-disabled');
                        ?>
                            <tr>
                                <td><code>#<?php echo e($s['score_id']); ?></code></td>
                                <td><strong><?php echo e($s['quiz_name']); ?></strong></td>
                                <td><?php echo e($s['t_name'] ?? 'Instructor'); ?></td>
                                <td><strong><?php echo e($s['score_point']); ?></strong> / <?php echo e($s['total_point']); ?></td>
                                <td>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $pct; ?>%
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($s['feedback'])): ?>
                                        <div style="background:var(--slate-50);padding:0.4rem 0.65rem;border-radius:var(--radius-sm);border:1px solid var(--slate-200);font-size:0.875rem;color:var(--slate-800);">
                                            &ldquo;<?php echo e($s['feedback']); ?>&rdquo;
                                        </div>
                                    <?php else: ?>
                                        <span style="color:var(--slate-400);font-size:0.875rem;"><em>No comments provided yet</em></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($s['date_created']); ?></td>
                                <td style="text-align:right;">
                                    <a href="quiz_attempt.php?qid=<?php echo e($s['quiz_id']); ?>" class="btn btn-outline btn-sm">
                                        Retake
                                    </a>
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

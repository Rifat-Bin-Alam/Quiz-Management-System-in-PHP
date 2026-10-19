<?php
// student_dashboard.php - Candidate Learning & Evaluation Portal
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

// Require Student authentication
require_student();

$student_id = (int)$_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

// Summary metrics
$total_attempts = 0;
$avg_percentage = 0;
$highest_score_pct = 0;

$m_stmt = $conn->prepare("SELECT COUNT(*) AS total, AVG((score_point / total_point) * 100) AS avg_pct, MAX((score_point / total_point) * 100) AS max_pct FROM score WHERE student_id = ?");
if ($m_stmt) {
    $m_stmt->bind_param("i", $student_id);
    $m_stmt->execute();
    $m_res = $m_stmt->get_result()->fetch_assoc();
    $total_attempts = (int)($m_res['total'] ?? 0);
    $avg_percentage = round((float)($m_res['avg_pct'] ?? 0), 1);
    $highest_score_pct = round((float)($m_res['max_pct'] ?? 0), 1);
    $m_stmt->close();
}

// Fetch available quizzes count
$available_count = 0;
if ($res = $conn->query("SELECT COUNT(*) AS total FROM quiz")) {
    $available_count = (int)($res->fetch_assoc()['total'] ?? 0);
}

// Fetch recent 5 scores
$recent_scores = [];
$s_stmt = $conn->prepare("SELECT s.score_point, s.total_point, s.quiz_id, q.quiz_name, s.score_id, s.date_created, s.feedback, t.t_name
                          FROM score s
                          JOIN quiz q ON s.quiz_id = q.quiz_id
                          LEFT JOIN teacher t ON q.teacher_id = t.teacher_id
                          WHERE s.student_id = ?
                          ORDER BY s.score_id DESC
                          LIMIT 5");
if ($s_stmt) {
    $s_stmt->bind_param("i", $student_id);
    $s_stmt->execute();
    $res = $s_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $recent_scores[] = $row;
    }
    $s_stmt->close();
}

// Fetch preview of 3 available quizzes for Explore Quizzes section
$preview_quizzes = [];
$pq_sql = "SELECT q.quiz_id, q.quiz_name, q.date_created, t.t_name,
           (SELECT COUNT(*) FROM question WHERE quiz_id = q.quiz_id) AS total_questions,
           (SELECT MAX(score_point) FROM score WHERE quiz_id = q.quiz_id AND student_id = ?) AS student_best_score,
           (SELECT total_point FROM score WHERE quiz_id = q.quiz_id AND student_id = ? ORDER BY score_id DESC LIMIT 1) AS student_total_point
           FROM quiz q
           JOIN teacher t ON q.teacher_id = t.teacher_id
           ORDER BY q.date_created DESC
           LIMIT 3";
$pq_stmt = $conn->prepare($pq_sql);
if ($pq_stmt) {
    $pq_stmt->bind_param("ii", $student_id, $student_id);
    $pq_stmt->execute();
    $pq_res = $pq_stmt->get_result();
    while ($row = $pq_res->fetch_assoc()) {
        $preview_quizzes[] = $row;
    }
    $pq_stmt->close();
}

$page_title = 'Student Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Candidate Assessment Center</h1>
        <p class="page-subtitle">Welcome back, <?php echo e($student_name); ?>. Monitor your academic performance and participate in quizzes.</p>
    </div>
    <div class="page-actions">
        <a href="take_quiz.php" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
            Explore Quizzes
        </a>
        <a href="view_score.php" class="btn btn-outline">Performance History</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Examinations Taken</div>
        <div class="stat-value"><?php echo $total_attempts; ?></div>
        <div class="stat-subtext">Completed assessment tests</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Average Performance</div>
        <div class="stat-value"><?php echo $avg_percentage; ?>%</div>
        <div class="stat-subtext">Mean across all evaluations</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Highest Score</div>
        <div class="stat-value" style="color:var(--emerald-600);"><?php echo $highest_score_pct; ?>%</div>
        <div class="stat-subtext">Personal best performance</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Available Assessments</div>
        <div class="stat-value"><?php echo $available_count; ?></div>
        <div class="stat-subtext">Open for examination</div>
    </div>
</div>

<!-- Explore Quizzes Preview Section with See More -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Explore Quizzes</h2>
            <span style="font-size:0.85rem;color:var(--slate-500);">Available examinations open for testing</span>
        </div>
        <a href="take_quiz.php" class="btn btn-outline btn-sm">Browse All Quizzes &rarr;</a>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:70px;">ID</th>
                        <th>Examination Title</th>
                        <th>Instructor</th>
                        <th>Questions</th>
                        <th>Status</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($preview_quizzes)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:2rem;color:var(--slate-400);">
                                No quizzes are currently open for examination.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($preview_quizzes as $pq): 
                            $q_count = (int)$pq['total_questions'];
                            $has_taken = ($pq['student_best_score'] !== null);
                        ?>
                            <tr>
                                <td><code>#<?php echo e($pq['quiz_id']); ?></code></td>
                                <td><strong><?php echo e($pq['quiz_name']); ?></strong></td>
                                <td><?php echo e($pq['t_name']); ?></td>
                                <td><span class="badge badge-info"><?php echo $q_count; ?> Questions</span></td>
                                <td>
                                    <?php if ($has_taken): ?>
                                        <span class="badge badge-success">Completed (<?php echo e($pq['student_best_score']); ?>/<?php echo e($pq['student_total_point']); ?>)</span>
                                    <?php else: ?>
                                        <span class="badge badge-pending">Not Attempted</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <?php if ($q_count > 0): ?>
                                        <a href="quiz_attempt.php?qid=<?php echo e($pq['quiz_id']); ?>" class="btn btn-primary btn-sm">
                                            <?php echo $has_taken ? 'Retake' : 'Start Quiz &rarr;'; ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="font-size:0.8rem;color:var(--slate-400);">Pending questions</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;">
        <span style="font-size:0.85rem;color:var(--slate-500);">Showing <?php echo count($preview_quizzes); ?> of <?php echo $available_count; ?> available examinations</span>
        <a href="take_quiz.php" class="btn btn-outline btn-sm">See More Quizzes &rarr;</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Recent Assessment Results</h2>
        <a href="view_score.php" style="font-size:0.875rem;color:var(--blue-600);text-decoration:none;font-weight:600;">View All Scores &rarr;</a>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Quiz Name</th>
                        <th>Instructor</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Performance Feedback</th>
                        <th>Submission Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_scores)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:2.5rem;color:var(--slate-400);">
                                <p style="font-size:1.05rem;font-weight:500;margin-bottom:0.5rem;">No examinations taken yet</p>
                                <p style="font-size:0.875rem;margin-bottom:1rem;">Explore available course quizzes and test your knowledge.</p>
                                <a href="take_quiz.php" class="btn btn-primary btn-sm">Take Your First Quiz</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_scores as $row): 
                            $tot = max(1, (int)$row['total_point']);
                            $pct = round(((int)$row['score_point'] / $tot) * 100, 1);
                            $badge_class = ($pct >= 70) ? 'badge-success' : (($pct >= 50) ? 'badge-pending' : 'badge-disabled');
                        ?>
                            <tr>
                                <td><strong><?php echo e($row['quiz_name']); ?></strong></td>
                                <td><?php echo e($row['t_name'] ?? 'Instructor'); ?></td>
                                <td><strong><?php echo e($row['score_point']); ?></strong> / <?php echo e($row['total_point']); ?></td>
                                <td>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $pct; ?>%
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($row['feedback'])): ?>
                                        <span style="color:var(--slate-700);font-size:0.875rem;">&ldquo;<?php echo e($row['feedback']); ?>&rdquo;</span>
                                    <?php else: ?>
                                        <span style="color:var(--slate-400);font-size:0.875rem;">Awaiting teacher remarks</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($row['date_created']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

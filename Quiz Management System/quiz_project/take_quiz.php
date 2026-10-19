<?php
// take_quiz.php - Available Assessments Directory for Students
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

// Require Student authentication
require_student();

$student_id = (int)$_SESSION['student_id'];

// Search parameter
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 5;
$offset = ($page - 1) * $per_page;
$show_all = (isset($_GET['show_all']) && $_GET['show_all'] === '1');

// Count total matching quizzes
$total_count = 0;
if (!empty($search)) {
    $c_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM quiz WHERE quiz_name LIKE ?");
    if ($c_stmt) {
        $param = '%' . $search . '%';
        $c_stmt->bind_param("s", $param);
        $c_stmt->execute();
        $total_count = (int)($c_stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $c_stmt->close();
    }
} else {
    if ($c_res = $conn->query("SELECT COUNT(*) AS total FROM quiz")) {
        $total_count = (int)($c_res->fetch_assoc()['total'] ?? 0);
    }
}

$total_pages = $total_count > 0 ? (int)ceil($total_count / $per_page) : 1;
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

// Fetch quizzes with prepared statements
$quizzes = [];
if (!empty($search)) {
    if ($show_all) {
        $sql = "SELECT q.quiz_id, q.quiz_name, q.date_created, t.t_name,
                (SELECT COUNT(*) FROM question WHERE quiz_id = q.quiz_id) AS total_questions,
                (SELECT MAX(score_point) FROM score WHERE quiz_id = q.quiz_id AND student_id = ?) AS student_best_score,
                (SELECT total_point FROM score WHERE quiz_id = q.quiz_id AND student_id = ? ORDER BY score_id DESC LIMIT 1) AS student_total_point
                FROM quiz q
                JOIN teacher t ON q.teacher_id = t.teacher_id
                WHERE q.quiz_name LIKE ?
                ORDER BY q.date_created DESC";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $param = '%' . $search . '%';
            $stmt->bind_param("iis", $student_id, $student_id, $param);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $quizzes[] = $row;
            }
            $stmt->close();
        }
    } else {
        $sql = "SELECT q.quiz_id, q.quiz_name, q.date_created, t.t_name,
                (SELECT COUNT(*) FROM question WHERE quiz_id = q.quiz_id) AS total_questions,
                (SELECT MAX(score_point) FROM score WHERE quiz_id = q.quiz_id AND student_id = ?) AS student_best_score,
                (SELECT total_point FROM score WHERE quiz_id = q.quiz_id AND student_id = ? ORDER BY score_id DESC LIMIT 1) AS student_total_point
                FROM quiz q
                JOIN teacher t ON q.teacher_id = t.teacher_id
                WHERE q.quiz_name LIKE ?
                ORDER BY q.date_created DESC
                LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $param = '%' . $search . '%';
            $stmt->bind_param("iisii", $student_id, $student_id, $param, $per_page, $offset);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $quizzes[] = $row;
            }
            $stmt->close();
        }
    }
} else {
    if ($show_all) {
        $sql = "SELECT q.quiz_id, q.quiz_name, q.date_created, t.t_name,
                (SELECT COUNT(*) FROM question WHERE quiz_id = q.quiz_id) AS total_questions,
                (SELECT MAX(score_point) FROM score WHERE quiz_id = q.quiz_id AND student_id = ?) AS student_best_score,
                (SELECT total_point FROM score WHERE quiz_id = q.quiz_id AND student_id = ? ORDER BY score_id DESC LIMIT 1) AS student_total_point
                FROM quiz q
                JOIN teacher t ON q.teacher_id = t.teacher_id
                ORDER BY q.date_created DESC";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $student_id, $student_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $quizzes[] = $row;
            }
            $stmt->close();
        }
    } else {
        $sql = "SELECT q.quiz_id, q.quiz_name, q.date_created, t.t_name,
                (SELECT COUNT(*) FROM question WHERE quiz_id = q.quiz_id) AS total_questions,
                (SELECT MAX(score_point) FROM score WHERE quiz_id = q.quiz_id AND student_id = ?) AS student_best_score,
                (SELECT total_point FROM score WHERE quiz_id = q.quiz_id AND student_id = ? ORDER BY score_id DESC LIMIT 1) AS student_total_point
                FROM quiz q
                JOIN teacher t ON q.teacher_id = t.teacher_id
                ORDER BY q.date_created DESC
                LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iiii", $student_id, $student_id, $per_page, $offset);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $quizzes[] = $row;
            }
            $stmt->close();
        }
    }
}

$page_title = 'Explore Quizzes';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Explore Quizzes</h1>
        <p class="page-subtitle">Find assessments by course exam title and test your knowledge</p>
    </div>
    <div class="page-actions">
        <a href="student_dashboard.php" class="btn btn-outline">&larr; Back to Dashboard</a>
        <a href="view_score.php" class="btn btn-subtle">My Scores</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Available Assessments</h2>
            <span style="font-size:0.85rem;color:var(--slate-500);">
                <?php if (!empty($search)): ?>
                    Found <?php echo $total_count; ?> exam(s) matching &ldquo;<?php echo e($search); ?>&rdquo;
                <?php else: ?>
                    <?php echo $total_count; ?> examination(s) published
                <?php endif; ?>
            </span>
        </div>

        <!-- Search Form by Exam Title -->
        <form method="GET" action="take_quiz.php" class="search-bar-form" role="search">
            <input type="text" name="search" class="form-control" placeholder="Search by exam title..." value="<?php echo e($search); ?>" aria-label="Search by exam title">
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
            <?php if (!empty($search)): ?>
                <a href="take_quiz.php" class="btn btn-subtle btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:70px;">ID</th>
                        <th>Examination Title</th>
                        <th>Course Instructor</th>
                        <th>Questions</th>
                        <th>Published</th>
                        <th>Previous Status</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($quizzes)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:3rem 1.5rem;color:var(--slate-500);">
                                <?php if (!empty($search)): ?>
                                    <p style="font-size:1.05rem;font-weight:600;margin-bottom:0.35rem;color:var(--slate-800);">No matching examinations found</p>
                                    <p style="font-size:0.875rem;color:var(--slate-500);margin-bottom:1rem;">We couldn't find any exams matching &ldquo;<strong><?php echo e($search); ?></strong>&rdquo;.</p>
                                    <a href="take_quiz.php" class="btn btn-outline btn-sm">View All Available Exams</a>
                                <?php else: ?>
                                    <p style="font-size:1.05rem;font-weight:600;margin-bottom:0.35rem;color:var(--slate-800);">No examinations currently available</p>
                                    <p style="font-size:0.875rem;color:var(--slate-400);">No assessment tests have been published by faculty yet. Please check back later.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($quizzes as $quiz): 
                            $q_count = (int)$quiz['total_questions'];
                            $has_taken = ($quiz['student_best_score'] !== null);
                        ?>
                            <tr>
                                <td><code>#<?php echo e($quiz['quiz_id']); ?></code></td>
                                <td><strong><?php echo e($quiz['quiz_name']); ?></strong></td>
                                <td><?php echo e($quiz['t_name']); ?></td>
                                <td>
                                    <span class="badge badge-info"><?php echo $q_count; ?> Questions</span>
                                </td>
                                <td><?php echo e($quiz['date_created']); ?></td>
                                <td>
                                    <?php if ($has_taken): ?>
                                        <span class="badge badge-success">
                                            Completed (<?php echo e($quiz['student_best_score']); ?> / <?php echo e($quiz['student_total_point']); ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-pending">Not Attempted</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <?php if ($q_count > 0): ?>
                                        <a href="quiz_attempt.php?qid=<?php echo e($quiz['quiz_id']); ?>" class="btn btn-primary btn-sm">
                                            <?php echo $has_taken ? 'Retake Quiz' : 'Start Quiz &rarr;'; ?>
                                        </a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline btn-sm btn-disabled" disabled title="No questions available yet">
                                            Unavailable
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- See More & Pagination Controls -->
    <?php if ($total_count > 0): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                <?php if ($show_all): ?>
                    Showing all <strong><?php echo $total_count; ?></strong> examinations
                <?php else: ?>
                    Showing <strong><?php echo min($total_count, $offset + 1); ?>&ndash;<?php echo min($total_count, $offset + count($quizzes)); ?></strong> of <strong><?php echo $total_count; ?></strong> examinations
                <?php endif; ?>
            </div>

            <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                <!-- See More / Show All Toggle -->
                <?php if ($total_count > $per_page): ?>
                    <?php if (!$show_all): ?>
                        <a href="take_quiz.php?show_all=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-outline btn-sm">
                            See More Exams (All <?php echo $total_count; ?>) &rarr;
                        </a>
                    <?php else: ?>
                        <a href="take_quiz.php?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-subtle btn-sm">
                            &larr; Show Paginated View
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Page Navigation when not in show_all mode -->
                <?php if (!$show_all && $total_pages > 1): ?>
                    <ul class="pagination-controls" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                            <li class="pagination-item">
                                <a href="take_quiz.php?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link" aria-label="Previous page">&larr;</a>
                            </li>
                        <?php else: ?>
                            <li class="pagination-item"><span class="pagination-link disabled">&larr;</span></li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="pagination-item">
                                <a href="take_quiz.php?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link <?php echo ($i === $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="pagination-item">
                                <a href="take_quiz.php?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link" aria-label="Next page">&rarr;</a>
                            </li>
                        <?php else: ?>
                            <li class="pagination-item"><span class="pagination-link disabled">&rarr;</span></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

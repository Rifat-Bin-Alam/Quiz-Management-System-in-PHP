<?php
// admin_dashboard.php - Administrator Oversight & Monitoring Interface
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

// Require Admin role
require_admin();

// Fetch summary metrics
$count_teachers = 0;
$count_pending = 0;
$count_students = 0;
$count_quizzes = 0;
$count_attempts = 0;

if ($res = $conn->query("SELECT COUNT(*) AS total FROM teacher")) {
    $count_teachers = (int)($res->fetch_assoc()['total'] ?? 0);
}
if ($res = $conn->query("SELECT COUNT(*) AS total FROM teacher WHERE status = 'pending'")) {
    $count_pending = (int)($res->fetch_assoc()['total'] ?? 0);
}
if ($res = $conn->query("SELECT COUNT(*) AS total FROM student")) {
    $count_students = (int)($res->fetch_assoc()['total'] ?? 0);
}
if ($res = $conn->query("SELECT COUNT(*) AS total FROM quiz")) {
    $count_quizzes = (int)($res->fetch_assoc()['total'] ?? 0);
}
if ($res = $conn->query("SELECT COUNT(*) AS total FROM score")) {
    $count_attempts = (int)($res->fetch_assoc()['total'] ?? 0);
}

// Search filters
$teacher_search = trim($_GET['teacher_search'] ?? '');
$student_search = trim($_GET['student_search'] ?? '');

// Fetch pending teachers
$pending_teachers = [];
$pending_sql = "SELECT teacher_id, t_name, email, phone_no, status FROM teacher WHERE status = 'pending' ORDER BY teacher_id DESC";
if ($res = $conn->query($pending_sql)) {
    while ($row = $res->fetch_assoc()) {
        $pending_teachers[] = $row;
    }
}

// Fetch preview of 5 teachers with prepared search
$all_teachers = [];
if (!empty($teacher_search)) {
    $stmt = $conn->prepare("SELECT teacher_id, t_name, email, phone_no, status FROM teacher WHERE t_name LIKE ? OR email LIKE ? ORDER BY teacher_id DESC LIMIT 5");
    $param = '%' . $teacher_search . '%';
    $stmt->bind_param("ss", $param, $param);
    $stmt->execute();
    $t_res = $stmt->get_result();
    while ($row = $t_res->fetch_assoc()) {
        $all_teachers[] = $row;
    }
    $stmt->close();
} else {
    $t_res = $conn->query("SELECT teacher_id, t_name, email, phone_no, status FROM teacher ORDER BY teacher_id DESC LIMIT 5");
    if ($t_res) {
        while ($row = $t_res->fetch_assoc()) {
            $all_teachers[] = $row;
        }
    }
}

// Fetch preview of 5 students with prepared search
$all_students = [];
if (!empty($student_search)) {
    $stmt = $conn->prepare("SELECT student_id, s_name, email, phone_no FROM student WHERE s_name LIKE ? OR email LIKE ? ORDER BY student_id DESC LIMIT 5");
    $param = '%' . $student_search . '%';
    $stmt->bind_param("ss", $param, $param);
    $stmt->execute();
    $s_res = $stmt->get_result();
    while ($row = $s_res->fetch_assoc()) {
        $all_students[] = $row;
    }
    $stmt->close();
} else {
    $s_res = $conn->query("SELECT student_id, s_name, email, phone_no FROM student ORDER BY student_id DESC LIMIT 5");
    if ($s_res) {
        while ($row = $s_res->fetch_assoc()) {
            $all_students[] = $row;
        }
    }
}

// Fetch preview of 5 quizzes for read-only administrative inspection
$all_quizzes = [];
$q_sql = "SELECT q.quiz_id, q.quiz_name, q.date_created, t.t_name,
          (SELECT COUNT(*) FROM question WHERE quiz_id = q.quiz_id) AS total_questions,
          (SELECT COUNT(*) FROM score WHERE quiz_id = q.quiz_id) AS total_attempts
          FROM quiz q
          LEFT JOIN teacher t ON q.teacher_id = t.teacher_id
          ORDER BY q.quiz_id DESC
          LIMIT 5";
if ($q_res = $conn->query($q_sql)) {
    while ($row = $q_res->fetch_assoc()) {
        $all_quizzes[] = $row;
    }
}

$page_title = 'Admin Monitoring Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Administrator Oversight</h1>
        <p class="page-subtitle">Real-time system monitoring, faculty verification, and academic data inspection</p>
    </div>
    <div class="page-actions">
        <!-- Disabled Admin creation button with explicit security note -->
        <div class="action-disabled-group">
            <button type="button" class="btn btn-primary btn-disabled" disabled aria-disabled="true" title="Creation is currently disabled">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Create Record
            </button>
            <span class="badge-disabled">Creation is currently disabled</span>
        </div>
    </div>
</div>

<!-- Key Performance & Audit Metrics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Pending Approvals</div>
        <div class="stat-value" style="color: <?php echo ($count_pending > 0) ? 'var(--amber-600)' : 'var(--slate-800)'; ?>;">
            <?php echo $count_pending; ?>
        </div>
        <div class="stat-subtext">Faculty awaiting review</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Verified Teachers</div>
        <div class="stat-value"><?php echo $count_teachers; ?></div>
        <div class="stat-subtext">Active faculty profiles</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Registered Students</div>
        <div class="stat-value"><?php echo $count_students; ?></div>
        <div class="stat-subtext">Candidate accounts</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Total Quizzes</div>
        <div class="stat-value"><?php echo $count_quizzes; ?></div>
        <div class="stat-subtext">Created across courses</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Quiz Submissions</div>
        <div class="stat-value"><?php echo $count_attempts; ?></div>
        <div class="stat-subtext">Evaluated attempts</div>
    </div>
</div>

<!-- Section 1: Pending Teachers (Teacher Approval Active) -->
<div class="card" id="pending-teachers-section">
    <div class="card-header">
        <div style="display:flex;align-items:center;gap:0.6rem;">
            <h2 class="card-title">Pending Faculty Verification</h2>
            <?php if ($count_pending > 0): ?>
                <span class="badge badge-pending"><?php echo $count_pending; ?> Action Required</span>
            <?php else: ?>
                <span class="badge badge-success">All Clear</span>
            <?php endif; ?>
        </div>
        <span style="font-size:0.85rem;color:var(--slate-500);">Teacher approval is active and fully functional</span>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:70px;">ID</th>
                        <th>Instructor Name</th>
                        <th>Email Address</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th style="text-align:right;">Administrative Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pending_teachers)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:2rem;color:var(--slate-400);">
                                No pending teacher registrations requiring approval.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pending_teachers as $teacher): ?>
                            <tr>
                                <td><code>#<?php echo e($teacher['teacher_id']); ?></code></td>
                                <td><strong><?php echo e($teacher['t_name']); ?></strong></td>
                                <td><?php echo e($teacher['email']); ?></td>
                                <td><?php echo e($teacher['phone_no']); ?></td>
                                <td><span class="badge badge-pending">Pending Approval</span></td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex;gap:0.4rem;align-items:center;">
                                        <!-- Active Teacher Approval Form -->
                                        <form method="POST" action="approve_teacher.php" style="display:inline;" onsubmit="return confirm('Approve teacher \'<?php echo e($teacher['t_name']); ?>\' for full system access?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="teacher_id" value="<?php echo e($teacher['teacher_id']); ?>">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                Approve
                                            </button>
                                        </form>

                                        <!-- Disabled Deletion Button with Tooltip and Badge -->
                                        <div class="action-disabled-group">
                                            <button type="button" class="btn btn-danger btn-sm btn-disabled" disabled aria-disabled="true" title="Deletion is currently disabled">
                                                Delete
                                            </button>
                                            <span class="badge-disabled">Deletion is currently disabled</span>
                                        </div>
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

<!-- Section 2: Faculty Directory -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Faculty Directory</h2>
        <form method="GET" action="admin_dashboard.php" class="search-bar-form">
            <input type="text" name="teacher_search" class="form-control" placeholder="Search teacher by name or email..." value="<?php echo e($teacher_search); ?>">
            <button type="submit" class="btn btn-outline btn-sm">Search</button>
            <?php if (!empty($teacher_search)): ?>
                <a href="admin_dashboard.php" class="btn btn-subtle btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:70px;">ID</th>
                        <th>Instructor Name</th>
                        <th>Email Address</th>
                        <th>Phone</th>
                        <th>Approval Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_teachers)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:2rem;color:var(--slate-400);">
                                No teacher accounts matching query.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($all_teachers as $teacher): ?>
                            <tr>
                                <td><code>#<?php echo e($teacher['teacher_id']); ?></code></td>
                                <td><strong><?php echo e($teacher['t_name']); ?></strong></td>
                                <td><?php echo e($teacher['email']); ?></td>
                                <td><?php echo e($teacher['phone_no']); ?></td>
                                <td>
                                    <?php if ($teacher['status'] === 'approved'): ?>
                                        <span class="badge badge-approved">Approved</span>
                                    <?php else: ?>
                                        <span class="badge badge-pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <!-- Disabled Deletion Button -->
                                    <div class="action-disabled-group">
                                        <button type="button" class="btn btn-danger btn-sm btn-disabled" disabled aria-disabled="true" title="Deletion is currently disabled">
                                            Delete
                                        </button>
                                        <span class="badge-disabled">Deletion is currently disabled</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;">
        <span style="font-size:0.85rem;color:var(--slate-500);">Showing preview of <?php echo count($all_teachers); ?> of <?php echo $count_teachers; ?> faculty records</span>
        <a href="admin_teachers.php<?php echo !empty($teacher_search) ? '?teacher_search=' . urlencode($teacher_search) : ''; ?>" class="btn btn-outline btn-sm">See More Faculty &rarr;</a>
    </div>
</div>

<!-- Section 3: Student Roster -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Student Roster</h2>
        <form method="GET" action="admin_dashboard.php" class="search-bar-form">
            <input type="text" name="student_search" class="form-control" placeholder="Search student by name or email..." value="<?php echo e($student_search); ?>">
            <button type="submit" class="btn btn-outline btn-sm">Search</button>
            <?php if (!empty($student_search)): ?>
                <a href="admin_dashboard.php" class="btn btn-subtle btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:70px;">ID</th>
                        <th>Student Name</th>
                        <th>Email Address</th>
                        <th>Phone</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_students)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center;padding:2rem;color:var(--slate-400);">
                                No student records matching query.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($all_students as $student): ?>
                            <tr>
                                <td><code>#<?php echo e($student['student_id']); ?></code></td>
                                <td><strong><?php echo e($student['s_name']); ?></strong></td>
                                <td><?php echo e($student['email']); ?></td>
                                <td><?php echo e($student['phone_no']); ?></td>
                                <td style="text-align:right;">
                                    <!-- Disabled Deletion Button -->
                                    <div class="action-disabled-group">
                                        <button type="button" class="btn btn-danger btn-sm btn-disabled" disabled aria-disabled="true" title="Deletion is currently disabled">
                                            Delete
                                        </button>
                                        <span class="badge-disabled">Deletion is currently disabled</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;">
        <span style="font-size:0.85rem;color:var(--slate-500);">Showing preview of <?php echo count($all_students); ?> of <?php echo $count_students; ?> student accounts</span>
        <a href="admin_students.php<?php echo !empty($student_search) ? '?student_search=' . urlencode($student_search) : ''; ?>" class="btn btn-outline btn-sm">See More Students &rarr;</a>
    </div>
</div>

<!-- Section 4: Academic Quizzes Overview (Read-Only Inspection) -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Assessments &amp; Quizzes Overview</h2>
        <span style="font-size:0.85rem;color:var(--slate-500);">Read-only oversight of all course examinations</span>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:70px;">ID</th>
                        <th>Quiz Name</th>
                        <th>Course Instructor</th>
                        <th>Date Published</th>
                        <th>Questions</th>
                        <th>Submissions</th>
                        <th style="text-align:right;">Administrative Inspection</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_quizzes)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:2rem;color:var(--slate-400);">
                                No quizzes created in the system yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($all_quizzes as $quiz): ?>
                            <tr>
                                <td><code>#<?php echo e($quiz['quiz_id']); ?></code></td>
                                <td><strong><?php echo e($quiz['quiz_name']); ?></strong></td>
                                <td><?php echo e($quiz['t_name'] ?? 'Unassigned'); ?></td>
                                <td><?php echo e($quiz['date_created']); ?></td>
                                <td><span class="badge badge-info"><?php echo e($quiz['total_questions']); ?> Questions</span></td>
                                <td><span class="badge badge-success"><?php echo e($quiz['total_attempts']); ?> Taken</span></td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex;gap:0.4rem;align-items:center;">
                                        <!-- Read-Only View Questions -->
                                        <a href="view_quiz.php?qid=<?php echo e($quiz['quiz_id']); ?>" class="btn btn-outline btn-sm">
                                            View Questions
                                        </a>

                                        <!-- Read-Only View Results -->
                                        <a href="view_results.php?qid=<?php echo e($quiz['quiz_id']); ?>" class="btn btn-outline btn-sm">
                                            View Results
                                        </a>

                                        <!-- Disabled Deletion Button -->
                                        <div class="action-disabled-group">
                                            <button type="button" class="btn btn-danger btn-sm btn-disabled" disabled aria-disabled="true" title="Deletion is currently disabled">
                                                Delete
                                            </button>
                                            <span class="badge-disabled">Deletion is currently disabled</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;">
        <span style="font-size:0.85rem;color:var(--slate-500);">Showing preview of <?php echo count($all_quizzes); ?> of <?php echo $count_quizzes; ?> published examinations</span>
        <a href="admin_quizzes.php" class="btn btn-outline btn-sm">See More Quizzes &rarr;</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

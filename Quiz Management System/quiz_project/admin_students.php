<?php
// admin_students.php - Complete Student Roster for Administrator
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

// Require Admin role
require_admin();

$search = trim($_GET['student_search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Count total matching students
$total_count = 0;
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM student WHERE s_name LIKE ? OR email LIKE ?");
    if ($stmt) {
        $param = '%' . $search . '%';
        $stmt->bind_param("ss", $param, $param);
        $stmt->execute();
        $total_count = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();
    }
} else {
    if ($res = $conn->query("SELECT COUNT(*) AS total FROM student")) {
        $total_count = (int)($res->fetch_assoc()['total'] ?? 0);
    }
}

$total_pages = $total_count > 0 ? (int)ceil($total_count / $per_page) : 1;
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

// Fetch students with prepared statement
$students = [];
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT student_id, s_name, email, phone_no FROM student WHERE s_name LIKE ? OR email LIKE ? ORDER BY student_id DESC LIMIT ? OFFSET ?");
    if ($stmt) {
        $param = '%' . $search . '%';
        $stmt->bind_param("ssii", $param, $param, $per_page, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
    }
} else {
    $stmt = $conn->prepare("SELECT student_id, s_name, email, phone_no FROM student ORDER BY student_id DESC LIMIT ? OFFSET ?");
    if ($stmt) {
        $stmt->bind_param("ii", $per_page, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
    }
}

$page_title = 'Student Roster — Full Directory';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Student Roster</h1>
        <p class="page-subtitle">Complete administrative directory of enrolled candidate student accounts</p>
    </div>
    <div class="page-actions">
        <a href="admin_dashboard.php" class="btn btn-outline">&larr; Back to Dashboard</a>
        <div class="action-disabled-group">
            <button type="button" class="btn btn-primary btn-disabled" disabled aria-disabled="true" title="Creation is currently disabled">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Student
            </button>
            <span class="badge-disabled">Creation is currently disabled</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">All Student Records</h2>
            <span style="font-size:0.85rem;color:var(--slate-500);">
                <?php if (!empty($search)): ?>
                    Found <?php echo $total_count; ?> student(s) matching &ldquo;<?php echo e($search); ?>&rdquo;
                <?php else: ?>
                    <?php echo $total_count; ?> registered student account(s)
                <?php endif; ?>
            </span>
        </div>

        <form method="GET" action="admin_students.php" class="search-bar-form" role="search">
            <input type="text" name="student_search" class="form-control" placeholder="Search student by name or email..." value="<?php echo e($search); ?>">
            <button type="submit" class="btn btn-outline btn-sm">Search</button>
            <?php if (!empty($search)): ?>
                <a href="admin_students.php" class="btn btn-subtle btn-sm">Reset</a>
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
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center;padding:3rem 1.5rem;color:var(--slate-500);">
                                <?php if (!empty($search)): ?>
                                    <p style="font-size:1.05rem;font-weight:600;margin-bottom:0.35rem;color:var(--slate-800);">No students found</p>
                                    <p style="font-size:0.875rem;color:var(--slate-500);margin-bottom:1rem;">No student records matched &ldquo;<strong><?php echo e($search); ?></strong>&rdquo;.</p>
                                    <a href="admin_students.php" class="btn btn-outline btn-sm">View Full Roster</a>
                                <?php else: ?>
                                    No student records currently exist.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
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

    <!-- Pagination -->
    <?php if ($total_count > 0): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Showing <strong><?php echo min($total_count, $offset + 1); ?>&ndash;<?php echo min($total_count, $offset + count($students)); ?></strong> of <strong><?php echo $total_count; ?></strong> students
            </div>

            <?php if ($total_pages > 1): ?>
                <ul class="pagination-controls" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <li class="pagination-item">
                            <a href="admin_students.php?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&student_search=' . urlencode($search) : ''; ?>" class="pagination-link" aria-label="Previous page">&larr;</a>
                        </li>
                    <?php else: ?>
                        <li class="pagination-item"><span class="pagination-link disabled">&larr;</span></li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="pagination-item">
                            <a href="admin_students.php?page=<?php echo $i; ?><?php echo !empty($search) ? '&student_search=' . urlencode($search) : ''; ?>" class="pagination-link <?php echo ($i === $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="pagination-item">
                            <a href="admin_students.php?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&student_search=' . urlencode($search) : ''; ?>" class="pagination-link" aria-label="Next page">&rarr;</a>
                        </li>
                    <?php else: ?>
                        <li class="pagination-item"><span class="pagination-link disabled">&rarr;</span></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

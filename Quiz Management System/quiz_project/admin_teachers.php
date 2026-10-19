<?php
// admin_teachers.php - Complete Faculty Directory for Administrator
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

// Require Admin role
require_admin();

$search = trim($_GET['teacher_search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Count total matching faculty records
$total_count = 0;
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM teacher WHERE t_name LIKE ? OR email LIKE ?");
    if ($stmt) {
        $param = '%' . $search . '%';
        $stmt->bind_param("ss", $param, $param);
        $stmt->execute();
        $total_count = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();
    }
} else {
    if ($res = $conn->query("SELECT COUNT(*) AS total FROM teacher")) {
        $total_count = (int)($res->fetch_assoc()['total'] ?? 0);
    }
}

$total_pages = $total_count > 0 ? (int)ceil($total_count / $per_page) : 1;
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

// Fetch faculty records with prepared statement
$teachers = [];
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT teacher_id, t_name, email, phone_no, status FROM teacher WHERE t_name LIKE ? OR email LIKE ? ORDER BY teacher_id DESC LIMIT ? OFFSET ?");
    if ($stmt) {
        $param = '%' . $search . '%';
        $stmt->bind_param("ssii", $param, $param, $per_page, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $teachers[] = $row;
        }
        $stmt->close();
    }
} else {
    $stmt = $conn->prepare("SELECT teacher_id, t_name, email, phone_no, status FROM teacher ORDER BY teacher_id DESC LIMIT ? OFFSET ?");
    if ($stmt) {
        $stmt->bind_param("ii", $per_page, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $teachers[] = $row;
        }
        $stmt->close();
    }
}

$page_title = 'Faculty Directory — Full Roster';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Faculty Directory</h1>
        <p class="page-subtitle">Complete administrative directory of registered and pending faculty members</p>
    </div>
    <div class="page-actions">
        <a href="admin_dashboard.php" class="btn btn-outline">&larr; Back to Dashboard</a>
        <div class="action-disabled-group">
            <button type="button" class="btn btn-primary btn-disabled" disabled aria-disabled="true" title="Creation is currently disabled">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Teacher
            </button>
            <span class="badge-disabled">Creation is currently disabled</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">All Faculty Records</h2>
            <span style="font-size:0.85rem;color:var(--slate-500);">
                <?php if (!empty($search)): ?>
                    Found <?php echo $total_count; ?> record(s) matching &ldquo;<?php echo e($search); ?>&rdquo;
                <?php else: ?>
                    <?php echo $total_count; ?> total faculty member(s)
                <?php endif; ?>
            </span>
        </div>

        <form method="GET" action="admin_teachers.php" class="search-bar-form" role="search">
            <input type="text" name="teacher_search" class="form-control" placeholder="Search teacher by name or email..." value="<?php echo e($search); ?>">
            <button type="submit" class="btn btn-outline btn-sm">Search</button>
            <?php if (!empty($search)): ?>
                <a href="admin_teachers.php" class="btn btn-subtle btn-sm">Reset</a>
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
                        <th>Status</th>
                        <th style="text-align:right;">Administrative Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($teachers)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:3rem 1.5rem;color:var(--slate-500);">
                                <?php if (!empty($search)): ?>
                                    <p style="font-size:1.05rem;font-weight:600;margin-bottom:0.35rem;color:var(--slate-800);">No faculty members found</p>
                                    <p style="font-size:0.875rem;color:var(--slate-500);margin-bottom:1rem;">No records matched &ldquo;<strong><?php echo e($search); ?></strong>&rdquo;.</p>
                                    <a href="admin_teachers.php" class="btn btn-outline btn-sm">View Full Directory</a>
                                <?php else: ?>
                                    No faculty records currently exist.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($teachers as $teacher): ?>
                            <tr>
                                <td><code>#<?php echo e($teacher['teacher_id']); ?></code></td>
                                <td><strong><?php echo e($teacher['t_name']); ?></strong></td>
                                <td><?php echo e($teacher['email']); ?></td>
                                <td><?php echo e($teacher['phone_no']); ?></td>
                                <td>
                                    <?php if ($teacher['status'] === 'approved'): ?>
                                        <span class="badge badge-approved">Approved</span>
                                    <?php else: ?>
                                        <span class="badge badge-pending">Pending Approval</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex;gap:0.4rem;align-items:center;">
                                        <?php if ($teacher['status'] === 'pending'): ?>
                                            <!-- Fully Functional Teacher Approval Action -->
                                            <form method="POST" action="approve_teacher.php" style="display:inline;" onsubmit="return confirm('Approve teacher \'<?php echo e($teacher['t_name']); ?>\'?');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="teacher_id" value="<?php echo e($teacher['teacher_id']); ?>">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                    Approve
                                                </button>
                                            </form>
                                        <?php endif; ?>

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

    <!-- Pagination -->
    <?php if ($total_count > 0): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Showing <strong><?php echo min($total_count, $offset + 1); ?>&ndash;<?php echo min($total_count, $offset + count($teachers)); ?></strong> of <strong><?php echo $total_count; ?></strong> faculty records
            </div>

            <?php if ($total_pages > 1): ?>
                <ul class="pagination-controls" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <li class="pagination-item">
                            <a href="admin_teachers.php?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&teacher_search=' . urlencode($search) : ''; ?>" class="pagination-link" aria-label="Previous page">&larr;</a>
                        </li>
                    <?php else: ?>
                        <li class="pagination-item"><span class="pagination-link disabled">&larr;</span></li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="pagination-item">
                            <a href="admin_teachers.php?page=<?php echo $i; ?><?php echo !empty($search) ? '&teacher_search=' . urlencode($search) : ''; ?>" class="pagination-link <?php echo ($i === $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="pagination-item">
                            <a href="admin_teachers.php?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&teacher_search=' . urlencode($search) : ''; ?>" class="pagination-link" aria-label="Next page">&rarr;</a>
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

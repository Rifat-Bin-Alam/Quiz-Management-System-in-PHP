<?php
// signup_teacher.php - Teacher Registration (Awaiting Admin Approval)
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $cpassword = $_POST['cpassword'] ?? '';

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid email address.";
    } elseif ($password !== $cpassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 4) {
        $error = "Password must be at least 4 characters long.";
    } else {
        // Check for duplicate email
        $check_stmt = $conn->prepare("SELECT teacher_id FROM teacher WHERE email = ? LIMIT 1");
        if ($check_stmt) {
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                $error = "An account with this email address already exists.";
            } else {
                // Securely hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $status = 'pending';

                $insert_stmt = $conn->prepare("INSERT INTO teacher (t_name, email, password, phone_no, status) VALUES (?, ?, ?, ?, ?)");
                if ($insert_stmt) {
                    $insert_stmt->bind_param("sssss", $name, $email, $hashed_password, $phone, $status);
                    if ($insert_stmt->execute()) {
                        $success = "Registration request submitted successfully! Your account is pending administrator review and approval. You will be able to log in once an administrator approves your account.";
                    } else {
                        $error = "Failed to register account. Please try again.";
                    }
                    $insert_stmt->close();
                } else {
                    $error = "Database preparation error.";
                }
            }
            $check_stmt->close();
        } else {
            $error = "Database service error.";
        }
    }
}

$page_title = 'Teacher Registration';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page-wrapper">
    <div class="auth-card" style="max-width: 480px;">
        <div class="auth-header">
            <div style="display:inline-flex;padding:0.75rem;background:var(--blue-50);border-radius:var(--radius-full);color:var(--blue-600);">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7.5" r="4"/>
                    <line x1="20" y1="8" x2="20" y2="14"/>
                    <line x1="23" y1="11" x2="17" y2="11"/>
                </svg>
            </div>
            <h1 class="auth-title">Teacher Registration</h1>
            <p class="auth-subtitle">Apply for teacher access. Admin approval is required before signing in.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert">
                <div class="alert-icon">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                </div>
                <div class="alert-content"><?php echo e($error); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <div class="alert-icon">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                </div>
                <div class="alert-content"><?php echo e($success); ?></div>
            </div>
            <div style="margin-top:1.5rem;text-align:center;">
                <a href="teacher_login.php" class="btn btn-primary">Proceed to Teacher Login</a>
            </div>
        <?php else: ?>

        <form method="POST" action="signup_teacher.php" novalidate>
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label class="form-label" for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Dr. Jane Smith" value="<?php echo e($_POST['name'] ?? ''); ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Academic Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="faculty@university.edu" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="017XXXXXXXX" value="<?php echo e($_POST['phone'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="password-input-wrapper">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Choose a secure password" required>
                    <button type="button" class="password-toggle-btn" data-target="password" aria-label="Show password">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="cpassword">Confirm Password</label>
                <div class="password-input-wrapper">
                    <input type="password" id="cpassword" name="cpassword" class="form-control" placeholder="Re-enter password" required>
                    <button type="button" class="password-toggle-btn" data-target="cpassword" aria-label="Show password">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:0.5rem;padding:0.75rem;">
                Submit Teacher Application
            </button>
        </form>

        <?php endif; ?>

        <div class="auth-footer">
            <p>Already have an approved account? <a href="teacher_login.php">Teacher Sign In</a></p>
            <p style="margin-top:0.5rem;"><a href="index.php">&larr; Return to Homepage</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
// includes/header.php - Universal Application Header & Academic Responsive Navbar
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';

$page_title = $page_title ?? 'Examination Portal';
$role = current_user_role();
$user_name = current_user_name();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo e($page_title); ?> — Assessment Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="animate-enter">

<nav class="app-navbar" aria-label="Main Navigation">
    <div class="navbar-inner">
        <div class="navbar-top-row">
            <a href="index.php" class="navbar-brand">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
                <span>EduQuiz</span>
                <span class="navbar-brand-badge">Pro</span>

            </a>

            <!-- Mobile Navigation Toggle Button -->
            <button class="navbar-toggle" type="button" id="navbarToggle" aria-expanded="false" aria-controls="navbarLinks" aria-label="Toggle navigation menu">
                <span class="navbar-toggle-bar"></span>
                <span class="navbar-toggle-bar"></span>
                <span class="navbar-toggle-bar"></span>
            </button>
        </div>

        <ul class="navbar-links" id="navbarLinks">
            <?php if ($role === 'admin'): ?>
                <li><a href="admin_dashboard.php" class="nav-link <?php echo ($current_page === 'admin_dashboard.php' || $current_page === 'admin_teachers.php' || $current_page === 'admin_students.php' || $current_page === 'admin_quizzes.php') ? 'active' : ''; ?>">Administration</a></li>
                <li class="user-profile-pill">
                    <span class="user-role-badge admin">Admin</span>
                    <span class="user-profile-name"><?php echo e($user_name); ?></span>
                </li>
                <li><a href="logout.php" class="btn btn-outline btn-sm nav-signout-btn">Sign Out</a></li>

            <?php elseif ($role === 'teacher'): ?>
                <li><a href="teacher_dashboard.php" class="nav-link <?php echo ($current_page === 'teacher_dashboard.php' || $current_page === 'view_quiz.php' || $current_page === 'view_results.php') ? 'active' : ''; ?>">Examinations</a></li>
                <li><a href="add_quiz.php" class="nav-link <?php echo ($current_page === 'add_quiz.php' || $current_page === 'add_question.php' || $current_page === 'edit_question.php') ? 'active' : ''; ?>">Create Exam</a></li>
                <li class="user-profile-pill">
                    <span class="user-role-badge teacher">Faculty</span>
                    <span class="user-profile-name"><?php echo e($user_name); ?></span>
                </li>
                <li><a href="logout.php" class="btn btn-outline btn-sm nav-signout-btn">Sign Out</a></li>

            <?php elseif ($role === 'student'): ?>
                <li><a href="student_dashboard.php" class="nav-link <?php echo ($current_page === 'student_dashboard.php') ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="take_quiz.php" class="nav-link <?php echo ($current_page === 'take_quiz.php' || $current_page === 'quiz_attempt.php') ? 'active' : ''; ?>">Take Examination</a></li>
                <li><a href="view_score.php" class="nav-link <?php echo ($current_page === 'view_score.php') ? 'active' : ''; ?>">Academic Transcript</a></li>
                <li class="user-profile-pill">
                    <span class="user-role-badge student">Candidate</span>
                    <span class="user-profile-name"><?php echo e($user_name); ?></span>
                </li>
                <li><a href="logout.php" class="btn btn-outline btn-sm nav-signout-btn">Sign Out</a></li>

            <?php else: ?>
                <li><a href="index.php" class="nav-link <?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">Portal Home</a></li>
                <li><a href="student_login.php" class="nav-link <?php echo ($current_page === 'student_login.php' || $current_page === 'signup_student.php') ? 'active' : ''; ?>">Candidate Login</a></li>
                <li><a href="teacher_login.php" class="nav-link <?php echo ($current_page === 'teacher_login.php' || $current_page === 'signup_teacher.php') ? 'active' : ''; ?>">Faculty Portal</a></li>
                <li><a href="admin_login.php" class="btn btn-outline btn-sm nav-admin-btn">Administrator</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<div class="app-container">
    <?php echo render_flash_messages(); ?>

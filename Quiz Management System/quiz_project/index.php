<?php
// index.php - Online Examination Portal
$page_title = 'Online Examination Portal';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Examination Portal Entry Header -->
<section class="hero-clean">
    <div class="hero-pill">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
        </svg>
        <span>Academic Assessment Environment</span>
    </div>

    <h1 class="hero-title">
        Online Examination Portal
    </h1>

    <p class="hero-subtitle">
        A secure digital assessment platform enabling candidates to undertake scheduled examinations and faculty members to curate and evaluate course assessments.
    </p>

    <!-- Primary User Entry Actions -->
    <div class="hero-actions">
        <a href="student_login.php" class="btn btn-primary btn-lg">
            Candidate Sign In &rarr;
        </a>
        <a href="teacher_login.php" class="btn btn-outline btn-lg">
            Faculty Portal
        </a>
    </div>

    <div style="font-size:0.875rem;color:var(--slate-500);margin-top:1.25rem;">
        New to the system? 
        <a href="signup_student.php" style="color:var(--blue-600);font-weight:600;text-decoration:none;">Candidate Registration</a> 
        &bull; 
        <a href="signup_teacher.php" style="color:var(--blue-600);font-weight:600;text-decoration:none;">Faculty Registration</a>
    </div>
</section>

<!-- System Capabilities Overview -->
<section class="benefits-grid" aria-label="System Capabilities">
    <!-- Candidate Assessment Card -->
    <div class="benefit-card">
        <div class="benefit-icon" style="background:var(--emerald-50);color:var(--emerald-600);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                <path d="M6 12v5c3 3 9 3 12 0v-5"/>
            </svg>
        </div>
        <h2 class="benefit-title">Candidate Assessments</h2>
        <p class="benefit-desc">
            Browse available course examinations, answer multiple-choice questions with real-time status tracking, and inspect immediate graded evaluation transcripts.
        </p>
    </div>

    <!-- Faculty Assessment Card -->
    <div class="benefit-card">
        <div class="benefit-icon" style="background:var(--blue-50);color:var(--blue-600);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
            </svg>
        </div>
        <h2 class="benefit-title">Faculty Assessment Suite</h2>
        <p class="benefit-desc">
            Author and publish examinations, curate question banks with designated answer keys, monitor submissions, and deliver qualitative student feedback.
        </p>
    </div>

    <!-- Administrator Oversight Card -->
    <div class="benefit-card">
        <div class="benefit-icon" style="background:var(--slate-100);color:var(--slate-800);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0110 0v4"/>
            </svg>
        </div>
        <h2 class="benefit-title">Institutional Oversight</h2>
        <p class="benefit-desc">
            Review and approve pending faculty registrations, inspect candidate registries, and monitor platform assessment participation securely.
        </p>
    </div>
</section>

<!-- Official Candidate Guidelines Notice -->
<div class="exam-instructions-notice" style="max-width:1040px;margin:0 auto 3.5rem;">
    <strong>Official Examination Notice:</strong> Enrolled candidates must log in with their assigned institutional credentials to undertake examinations. Ensure an uninterrupted connection before entering the examination room. All examination attempts are recorded and submitted to course instructors for evaluation.
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

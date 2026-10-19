<?php
// quiz_attempt.php - Candidate Interactive Examination Interface
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/security.php';

// Require Student authentication
require_student();

$student_id = (int)$_SESSION['student_id'];
$quiz_id = validate_id($_GET['qid'] ?? null);

if (!$quiz_id) {
    set_flash('error', 'Please select a valid quiz examination to attempt.');
    header("Location: take_quiz.php");
    exit();
}

// Fetch quiz info using prepared statement
$q_stmt = $conn->prepare("SELECT q.quiz_id, q.quiz_name, t.t_name FROM quiz q LEFT JOIN teacher t ON q.teacher_id = t.teacher_id WHERE q.quiz_id = ? LIMIT 1");
if (!$q_stmt) {
    die("Database error.");
}
$q_stmt->bind_param("i", $quiz_id);
$q_stmt->execute();
$q_res = $q_stmt->get_result();

if ($q_res->num_rows === 0) {
    $q_stmt->close();
    set_flash('error', 'Requested quiz examination not found.');
    header("Location: take_quiz.php");
    exit();
}

$quiz = $q_res->fetch_assoc();
$q_stmt->close();

// Fetch questions for this quiz
$questions = [];
$qn_stmt = $conn->prepare("SELECT question_id, question_text, option1, option2, option3 FROM question WHERE quiz_id = ? ORDER BY question_id ASC");
if ($qn_stmt) {
    $qn_stmt->bind_param("i", $quiz_id);
    $qn_stmt->execute();
    $res = $qn_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $questions[] = $row;
    }
    $qn_stmt->close();
}

if (empty($questions)) {
    set_flash('error', 'This quiz does not have any questions registered yet.');
    header("Location: take_quiz.php");
    exit();
}

$total_q = count($questions);
$page_title = 'Examination — ' . $quiz['quiz_name'];
require_once __DIR__ . '/includes/header.php';
?>

<!-- Official Examination Banner & Candidate Credentials -->
<div class="exam-header-banner">
    <div class="exam-banner-top">
        <div>
            <div class="exam-portal-badge-group">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
                <span>Official Digital Examination Hall</span>
            </div>
            <h1 class="exam-banner-title"><?php echo e($quiz['quiz_name']); ?></h1>
        </div>
        <div>
            <a href="take_quiz.php" class="btn btn-outline btn-sm" onclick="return confirm('Exit this examination session? Any unsubmitted responses will not be saved.');">
                &larr; Exit Examination
            </a>
        </div>
    </div>

    <div class="exam-candidate-grid">
        <div class="exam-meta-item">
            <span class="exam-meta-label">Candidate Name</span>
            <span class="exam-meta-value"><?php echo e($_SESSION['student_name'] ?? 'Candidate'); ?></span>
        </div>
        <div class="exam-meta-item">
            <span class="exam-meta-label">Student ID</span>
            <span class="exam-meta-value">#STU-<?php echo str_pad((string)$student_id, 4, '0', STR_PAD_LEFT); ?></span>
        </div>
        <div class="exam-meta-item">
            <span class="exam-meta-label">Course Instructor</span>
            <span class="exam-meta-value"><?php echo e($quiz['t_name'] ?? 'Department Faculty'); ?></span>
        </div>
        <div class="exam-meta-item">
            <span class="exam-meta-label">Total Questions</span>
            <span class="exam-meta-value"><?php echo $total_q; ?> Multiple-Choice</span>
        </div>
    </div>

    <div class="exam-instructions-notice">
        <strong>Examination Instructions:</strong> Select the best response for each question. You may navigate directly between questions using the question jump palette below. Review your answers carefully before confirming your final submission. Unanswered questions will receive a score of zero upon grading.
    </div>
</div>

<!-- Sticky Live Question Tracker & Navigation Palette -->
<div class="exam-tracker-bar" id="examTrackerBar">
    <div class="exam-tracker-row">
        <div class="exam-tracker-status">
            Progress: <strong id="answered-count">0</strong> of <strong><?php echo $total_q; ?></strong> Answered
            (<span id="remaining-count"><?php echo $total_q; ?></span> Remaining)
        </div>
        <div style="font-size:0.825rem;color:var(--slate-500);font-weight:600;" id="progress-percent">
            0% Complete
        </div>
    </div>
    <div class="exam-progress-track" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" aria-label="Examination Completion Progress">
        <div class="exam-progress-fill" id="exam-progress-bar" style="width: 0%;"></div>
    </div>
    <div class="question-palette-section">
        <span class="palette-label">Question Jump:</span>
        <div class="question-palette" id="questionPalette" role="navigation" aria-label="Question Jump Palette">
            <?php foreach ($questions as $idx => $qn): 
                $num = $idx + 1;
                $qid = (int)$qn['question_id'];
            ?>
                <a href="#qc-<?php echo $qid; ?>" class="palette-btn unanswered" id="nav-pill-<?php echo $qid; ?>" data-qid="<?php echo $qid; ?>" title="Jump to Question <?php echo $num; ?>">
                    <?php echo $num; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Examination Form with Question Cards -->
<form method="POST" action="submit_quiz.php" id="quiz-attempt-form" data-total="<?php echo $total_q; ?>" novalidate>
    <?php echo csrf_field(); ?>
    <input type="hidden" name="quiz_id" value="<?php echo e($quiz_id); ?>">

    <div style="max-width: 860px; margin: 0 auto;">
        <?php $q_index = 1; foreach ($questions as $qn): 
            $qid = (int)$qn['question_id'];
        ?>
            <div class="question-card" id="qc-<?php echo $qid; ?>" data-qid="<?php echo $qid; ?>">
                <div class="question-header">
                    <div class="question-header-meta">
                        <span class="question-badge">Question <?php echo $q_index; ?> of <?php echo $total_q; ?></span>
                        <span class="q-status-pill unanswered" id="qstatus-<?php echo $qid; ?>">Unanswered</span>
                    </div>
                    <div class="question-prompt"><?php echo e($qn['question_text']); ?></div>
                </div>

                <div class="option-group" role="radiogroup" aria-labelledby="qc-<?php echo $qid; ?>">
                    <!-- Option 1 -->
                    <label class="option-label" for="opt_<?php echo $qid; ?>_1">
                        <input type="radio" id="opt_<?php echo $qid; ?>_1" name="answers[<?php echo $qid; ?>]" value="<?php echo e($qn['option1']); ?>" data-qid="<?php echo $qid; ?>">
                        <span><?php echo e($qn['option1']); ?></span>
                    </label>

                    <!-- Option 2 -->
                    <label class="option-label" for="opt_<?php echo $qid; ?>_2">
                        <input type="radio" id="opt_<?php echo $qid; ?>_2" name="answers[<?php echo $qid; ?>]" value="<?php echo e($qn['option2']); ?>" data-qid="<?php echo $qid; ?>">
                        <span><?php echo e($qn['option2']); ?></span>
                    </label>

                    <!-- Option 3 -->
                    <label class="option-label" for="opt_<?php echo $qid; ?>_3">
                        <input type="radio" id="opt_<?php echo $qid; ?>_3" name="answers[<?php echo $qid; ?>]" value="<?php echo e($qn['option3']); ?>" data-qid="<?php echo $qid; ?>">
                        <span><?php echo e($qn['option3']); ?></span>
                    </label>
                </div>
            </div>
        <?php $q_index++; endforeach; ?>

        <!-- Bottom Submission Bar -->
        <div class="card quiz-submission-card" style="margin-top: 2rem;">
            <div class="card-body quiz-submission-body" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div>
                    <strong style="color:var(--slate-800);">Finished your examination?</strong>
                    <p style="font-size:0.875rem;color:var(--slate-500);margin:0;">Review your selections above or confirm final submission.</p>
                </div>
                <div class="quiz-submission-actions" style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                    <a href="take_quiz.php" class="btn btn-outline" onclick="return confirm('Cancel examination attempt and exit? Any responses will be lost.');">Discard Attempt</a>
                    <button type="button" class="btn btn-primary" id="examOpenModalBtn">
                        Submit Examination &rarr;
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Examination Final Submission Confirmation Modal -->
<div class="exam-modal-backdrop" id="examSubmitModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="exam-modal">
        <div class="exam-modal-header">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--navy-800)" stroke-width="2" aria-hidden="true">
                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h2 class="exam-modal-title" id="modalTitle">Confirm Examination Submission</h2>
        </div>
        <div class="exam-modal-body">
            <p>You are about to conclude your examination and submit your answers for formal evaluation. Once submitted, your answers cannot be altered.</p>
            
            <div class="exam-modal-stats">
                <div class="exam-modal-stat-box">
                    <div class="exam-modal-stat-num" id="modal-answered-count" style="color:var(--emerald-600);">0</div>
                    <div class="exam-modal-stat-txt">Answered</div>
                </div>
                <div class="exam-modal-stat-box">
                    <div class="exam-modal-stat-num" id="modal-unanswered-count" style="color:var(--amber-600);"><?php echo $total_q; ?></div>
                    <div class="exam-modal-stat-txt">Unanswered</div>
                </div>
                <div class="exam-modal-stat-box">
                    <div class="exam-modal-stat-num" style="color:var(--navy-950);"><?php echo $total_q; ?></div>
                    <div class="exam-modal-stat-txt">Total</div>
                </div>
            </div>

            <div id="modal-warning-text" style="display:none;padding:0.75rem 1rem;background-color:var(--amber-50);border:1px solid var(--amber-100);border-radius:var(--radius-sm);color:var(--amber-700);font-size:0.875rem;margin-bottom:1rem;">
                <strong>Notice:</strong> You have unanswered questions remaining. Unanswered questions will receive a score of zero.
            </div>
            <div id="modal-ready-text" style="display:none;padding:0.75rem 1rem;background-color:var(--emerald-50);border:1px solid var(--emerald-100);border-radius:var(--radius-sm);color:var(--emerald-700);font-size:0.875rem;margin-bottom:1rem;">
                All questions have been completed and are ready for grading.
            </div>
        </div>
        <div class="exam-modal-actions">
            <button type="button" class="btn btn-outline" id="modalCancelBtn">
                &larr; Review Answers
            </button>
            <button type="button" class="btn btn-primary" id="modalConfirmSubmitBtn">
                Confirm & Submit Examination &rarr;
            </button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

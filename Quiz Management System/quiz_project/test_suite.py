import os
import re
import sys

project_dir = r"f:\IUBAT\8th Semester\CSC 387\quiz_project"

php_files = [f for f in os.listdir(project_dir) if f.endswith('.php')]
includes_files = [os.path.join('includes', f) for f in os.listdir(os.path.join(project_dir, 'includes')) if f.endswith('.php')]
all_php = php_files + includes_files

print(f"Total PHP files to inspect: {len(all_php)}")

failures = []
passes = []

def report(test_name, passed, detail=""):
    if passed:
        passes.append(test_name)
        print(f"  [PASS] {test_name}" + (f": {detail}" if detail else ""))
    else:
        failures.append((test_name, detail))
        print(f"  [FAIL] {test_name}: {detail}")

print("\n--- TEST SUITE 1: SYNTAX & DELIMITER INTEGRITY ---")
for rel_path in all_php:
    full_path = os.path.join(project_dir, rel_path)
    with open(full_path, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()

    has_open_php = "<?php" in content
    report(f"PHP open tag present in {rel_path}", has_open_php)

print("\n--- TEST SUITE 2: SQL INJECTION STATIC AUDIT ---")
direct_sql_concats = []
for rel_path in all_php:
    full_path = os.path.join(project_dir, rel_path)
    with open(full_path, 'r', encoding='utf-8', errors='ignore') as f:
        lines = f.readlines()
    for idx, line in enumerate(lines, 1):
        if re.search(r"""(SELECT|INSERT|UPDATE|DELETE)[^"']*\$""", line, re.IGNORECASE):
            if "query(" in line or "mysqli_query(" in line:
                direct_sql_concats.append(f"{rel_path}:{idx}: {line.strip()}")

report("Zero raw user-concatenated SQL queries in execution calls", len(direct_sql_concats) == 0, f"Found: {direct_sql_concats}")

print("\n--- TEST SUITE 3: ADMIN PERMISSIONS & DISABLED ACTIONS ---")
# 1. delete_teacher.php
with open(os.path.join(project_dir, 'delete_teacher.php'), 'r', encoding='utf-8') as f:
    dt = f.read()
report("delete_teacher.php enforces require_admin", "require_admin" in dt)
report("delete_teacher.php returns 403 Forbidden", "403" in dt)
report("delete_teacher.php executes ZERO DELETE statements", "DELETE FROM" not in dt)

# 2. delete_student.php
with open(os.path.join(project_dir, 'delete_student.php'), 'r', encoding='utf-8') as f:
    ds = f.read()
report("delete_student.php enforces require_admin", "require_admin" in ds)
report("delete_student.php returns 403 Forbidden", "403" in ds)
report("delete_student.php executes ZERO DELETE statements", "DELETE FROM" not in ds)

# 3. admin_dashboard.php disabled buttons
with open(os.path.join(project_dir, 'admin_dashboard.php'), 'r', encoding='utf-8') as f:
    ad = f.read()
report("admin_dashboard.php has disabled delete teacher buttons", "Deletion is currently disabled" in ad and "btn-disabled" in ad)
report("admin_dashboard.php has disabled delete student buttons", "Deletion is currently disabled" in ad and "disabled" in ad)
report("admin_dashboard.php has disabled create buttons", "Creation is currently disabled" in ad)

# 4. Endpoints blocked for admin
for admin_blocked in ['add_quiz.php', 'add_question.php', 'edit_question.php', 'delete_quiz.php', 'delete_question.php']:
    with open(os.path.join(project_dir, admin_blocked), 'r', encoding='utf-8') as f:
        content = f.read()
    report(f"{admin_blocked} has server-side block_admin_action", "block_admin_action" in content)

print("\n--- TEST SUITE 4: TEACHER APPROVAL WORKFLOW ---")
with open(os.path.join(project_dir, 'approve_teacher.php'), 'r', encoding='utf-8') as f:
    at = f.read()
report("approve_teacher.php enforces require_admin", "require_admin" in at)
report("approve_teacher.php enforces POST method", "REQUEST_METHOD'] !== 'POST'" in at)
report("approve_teacher.php enforces CSRF validation", "require_csrf" in at)
report("approve_teacher.php validates integer ID", "validate_id" in at)
report("approve_teacher.php updates teacher status to approved via prepared statement", "UPDATE teacher SET status = 'approved'" in at and "bind_param" in at)
report("admin_dashboard.php contains active approve_teacher form", "action=\"approve_teacher.php\"" in ad and "csrf_field()" in ad)

print("\n--- TEST SUITE 5: AUTHENTICATION & CSRF IN FORMS ---")
form_pairs = [
    ('admin_login.php', 'admin_login.php'),
    ('teacher_login.php', 'teacher_login.php'),
    ('student_login.php', 'student_login.php'),
    ('signup_teacher.php', 'signup_teacher.php'),
    ('signup_student.php', 'signup_student.php'),
    ('add_quiz.php', 'add_quiz.php'),
    ('add_question.php', 'add_question.php'),
    ('edit_question.php', 'edit_question.php'),
    ('quiz_attempt.php', 'submit_quiz.php'),
    ('view_results.php', 'view_results.php'),
]
for form_view, form_handler in form_pairs:
    with open(os.path.join(project_dir, form_view), 'r', encoding='utf-8') as f:
        v_content = f.read()
    with open(os.path.join(project_dir, form_handler), 'r', encoding='utf-8') as f:
        h_content = f.read()
    report(f"CSRF protection field present in {form_view}", "csrf_field()" in v_content)
    report(f"CSRF verification required in {form_handler}", "require_csrf()" in h_content)

print("\n--- TEST SUITE 6: SERVER-SIDE QUIZ SCORING ---")
with open(os.path.join(project_dir, 'submit_quiz.php'), 'r', encoding='utf-8') as f:
    sq = f.read()
report("submit_quiz.php enforces require_student", "require_student" in sq)
report("submit_quiz.php queries DB for authoritative answers", "SELECT question_id, correct_answer FROM question" in sq)
report("submit_quiz.php records score using prepared statement", "INSERT INTO score" in sq and "bind_param" in sq)

print("\n--- TEST SUITE 7: PASSWORD MIGRATION & REHASH ---")
with open(os.path.join(project_dir, 'includes', 'security.php'), 'r', encoding='utf-8') as f:
    sec = f.read()
report("verify_and_rehash_password handles password_verify", "password_verify" in sec)
report("verify_and_rehash_password handles plaintext fallback", "input_pass === $stored_pass" in sec)
report("verify_and_rehash_password rehashes to bcrypt", "password_hash" in sec and "PASSWORD_DEFAULT" in sec)

print("\n--- TEST SUITE 8: EXPLORE QUIZZES SEARCH & SEE MORE ---")
with open(os.path.join(project_dir, 'take_quiz.php'), 'r', encoding='utf-8') as f:
    tq = f.read()
report("take_quiz.php enforces require_student", "require_student" in tq)
report("take_quiz.php has search form input and button", 'name="search"' in tq and 'Search' in tq)
report("take_quiz.php uses prepared statement for quiz_name search", "quiz_name LIKE ?" in tq and "bind_param" in tq)
report("take_quiz.php has See More Exams toggle/link", "See More Exams" in tq)
report("take_quiz.php preserves search term in pagination", "&search=" in tq)

with open(os.path.join(project_dir, 'student_dashboard.php'), 'r', encoding='utf-8') as f:
    sd = f.read()
report("student_dashboard.php has Explore Quizzes section with See More link", "Explore Quizzes" in sd and "take_quiz.php" in sd and "See More Quizzes" in sd)

print("\n--- TEST SUITE 9: ADMIN DASHBOARD SEE MORE & LISTING PAGES ---")
with open(os.path.join(project_dir, 'admin_dashboard.php'), 'r', encoding='utf-8') as f:
    ad = f.read()
report("admin_dashboard.php has See More Faculty link", "admin_teachers.php" in ad and "See More Faculty" in ad)
report("admin_dashboard.php has See More Students link", "admin_students.php" in ad and "See More Students" in ad)
report("admin_dashboard.php has See More Quizzes link", "admin_quizzes.php" in ad and "See More Quizzes" in ad)

with open(os.path.join(project_dir, 'admin_teachers.php'), 'r', encoding='utf-8') as f:
    at_page = f.read()
report("admin_teachers.php enforces require_admin", "require_admin" in at_page)
report("admin_teachers.php uses prepared statement for teacher search", "t_name LIKE ? OR email LIKE ?" in at_page and "bind_param" in at_page)
report("admin_teachers.php contains active approve_teacher form", "approve_teacher.php" in at_page and "csrf_field()" in at_page)
report("admin_teachers.php has disabled delete buttons", "Deletion is currently disabled" in at_page and "btn-disabled" in at_page)

with open(os.path.join(project_dir, 'admin_students.php'), 'r', encoding='utf-8') as f:
    as_page = f.read()
report("admin_students.php enforces require_admin", "require_admin" in as_page)
report("admin_students.php uses prepared statement for student search", "s_name LIKE ? OR email LIKE ?" in as_page and "bind_param" in as_page)
report("admin_students.php has disabled delete buttons", "Deletion is currently disabled" in as_page and "btn-disabled" in as_page)

with open(os.path.join(project_dir, 'admin_quizzes.php'), 'r', encoding='utf-8') as f:
    aq_page = f.read()
report("admin_quizzes.php enforces require_admin", "require_admin" in aq_page)
report("admin_quizzes.php uses prepared statement for quiz search", "quiz_name LIKE ? OR t.t_name LIKE ?" in aq_page and "bind_param" in aq_page)
report("admin_quizzes.php has read-only view question and result links", "view_quiz.php" in aq_page and "view_results.php" in aq_page)
report("admin_quizzes.php has disabled delete buttons", "Deletion is currently disabled" in aq_page and "btn-disabled" in aq_page)

print("\n--- TEST SUITE 10: FOOTER LINKS & ADMIN LOGIN BUTTON ---")
with open(os.path.join(project_dir, 'includes', 'footer.php'), 'r', encoding='utf-8') as f:
    ft = f.read()
report("footer has 2026 Rifat Bin Alam copyright", "2026 Rifat Bin Alam. All rights reserved." in ft)
report("footer has GitHub project link with target _blank and noopener", "https://github.com/Rifat-Bin-Alam/Quiz-Management-System-in-PHP" in ft and 'target="_blank"' in ft and 'rel="noopener noreferrer"' in ft)
report("footer has Portfolio link with target _blank and noopener", "https://rifat-bin-alam.vercel.app/" in ft and 'target="_blank"' in ft and 'rel="noopener noreferrer"' in ft)

with open(os.path.join(project_dir, 'index.php'), 'r', encoding='utf-8') as f:
    idx = f.read()
with open(os.path.join(project_dir, 'includes', 'header.php'), 'r', encoding='utf-8') as f:
    hdr = f.read()
report("header.php has Admin Login button with nav-admin-btn styling", "admin_login.php" in hdr and "nav-admin-btn" in hdr)
report("index.php does not have admin login in hero actions", "admin_login.php" not in idx.split('hero-actions')[1].split('</div>')[0])
report("header.php does not contain University badge beside brand", 'navbar-brand-badge">University' not in hdr)
report("header.php brand is kept like the old one (EduQuiz Pro)", "EduQuiz" in hdr and 'navbar-brand-badge">Pro' in hdr)


print("\n--- TEST SUITE 11: DEMO CREDENTIALS & AUTOFILL ---")
with open(os.path.join(project_dir, 'includes', 'auth.php'), 'r', encoding='utf-8') as f:
    auth_php = f.read()
report("includes/auth.php has is_demo_enabled checking APP_ENV", "is_demo_enabled" in auth_php and "APP_ENV" in auth_php)
report("includes/auth.php has render_demo_credentials helper", "render_demo_credentials" in auth_php)

with open(os.path.join(project_dir, 'student_login.php'), 'r', encoding='utf-8') as f:
    sl = f.read()
report("student_login.php displays demo credentials r@gmail.com and labonno@gmail.com", "r@gmail.com" in sl and "labonno@gmail.com" in sl and "render_demo_credentials" in sl)

with open(os.path.join(project_dir, 'teacher_login.php'), 'r', encoding='utf-8') as f:
    tl = f.read()
report("teacher_login.php displays demo credentials rifat@gmail.com and labonno@gmail.com", "rifat@gmail.com" in tl and "labonno@gmail.com" in tl and "render_demo_credentials" in tl)

with open(os.path.join(project_dir, 'admin_login.php'), 'r', encoding='utf-8') as f:
    al = f.read()
report("admin_login.php displays demo credentials rifat5566123@gmail.com and labonno@gmail.com", "rifat5566123@gmail.com" in al and "labonno@gmail.com" in al and "render_demo_credentials" in al)

with open(os.path.join(project_dir, 'assets', 'js', 'main.js'), 'r', encoding='utf-8') as f:
    js = f.read()
report("main.js has demo-autofill-btn handler with preventDefault (no auto-submit)", "demo-autofill-btn" in js and "preventDefault" in js and "emailInput.value" in js and "passwordInput.value" in js)

with open(os.path.join(project_dir, 'sql.php'), 'r', encoding='utf-8') as f:
    sql_file = f.read()
report("sql.php defines APP_ENV development default", "APP_ENV" in sql_file and "development" in sql_file)

print("\n--- TEST SUITE 12: UNIVERSITY EXAMINATION PORTAL & EXAM ROOM ---")
with open(os.path.join(project_dir, 'quiz_attempt.php'), 'r', encoding='utf-8') as f:
    qa = f.read()
report("quiz_attempt.php contains candidate credentials banner", "exam-header-banner" in qa and "exam-candidate-grid" in qa)
report("quiz_attempt.php contains live question tracker bar", "exam-tracker-bar" in qa and "answered-count" in qa and "exam-progress-bar" in qa)
report("quiz_attempt.php contains question palette jump navigation", "question-palette" in qa and "palette-btn" in qa)
report("quiz_attempt.php contains question card status badges", "q-status-pill" in qa and "qc-" in qa)
report("quiz_attempt.php contains submission confirmation modal", "examSubmitModal" in qa and "modalConfirmSubmitBtn" in qa and "modalCancelBtn" in qa)

with open(os.path.join(project_dir, 'index.php'), 'r', encoding='utf-8') as f:
    idx2 = f.read()
report("index.php presents Online Examination Portal title", "Online Examination Portal" in idx2)
report("index.php has candidate and faculty portal entry actions", "student_login.php" in idx2 and "teacher_login.php" in idx2)
report("index.php contains official candidate examination guidelines notice", "exam-instructions-notice" in idx2)

with open(os.path.join(project_dir, 'assets', 'js', 'main.js'), 'r', encoding='utf-8') as f:
    js2 = f.read()
report("main.js contains updateExamProgress live controller", "updateExamProgress" in js2 and "answeredQids" in js2)
report("main.js handles smooth jump scrolling for palette buttons", "palette-btn" in js2 and "scrollIntoView" in js2)
report("main.js intercepts form submission with confirmation modal", "examSubmitModal" in js2 and "submissionConfirmed" in js2)

print("\n==========================================")
print(f"TEST RESULTS SUMMARY: {len(passes)} PASSED, {len(failures)} FAILED")

print("==========================================")
if failures:
    sys.exit(1)


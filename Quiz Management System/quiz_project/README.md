# EduQuiz Pro — Digital Examination & Quiz Management System

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-4479a1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-Academic%20%2F%20MIT-emerald.svg)](LICENSE)
[![Tests Passing](https://img.shields.io/badge/Test%20Suite-126%2F126%20Passed-success.svg)](test_suite.py)
[![Syntax Check](https://img.shields.io/badge/PHP%20Syntax-34%2F34%20Clean-success.svg)](check_syntax.py)

**EduQuiz Pro** is a digital examination and course assessment platform developed in plain PHP, MySQL/MariaDB, HTML5, CSS3, and vanilla JavaScript (zero heavy dependencies or frameworks).

Designed with an academic institutional aesthetic, it provides a distraction-free examination hall for candidates, an assessment authoring suite for faculty instructors, and an oversight administration panel with role permissions and security hardening.

- **Live Website:** [https://quiz-management-system.freedev.app/](https://quiz-management-system.freedev.app/)
- **GitHub Repository:** [https://github.com/Rifat-Bin-Alam/Quiz-Management-System-in-PHP](https://github.com/Rifat-Bin-Alam/Quiz-Management-System-in-PHP)
- **Author Portfolio:** [https://rifat-bin-alam.vercel.app/](https://rifat-bin-alam.vercel.app/)

---

## Table of Contents

1. [Key Features by Role](#1-key-features-by-role)
2. [Interactive Examination Experience](#2-interactive-examination-experience)
3. [Security Architecture & Hardening](#3-security-architecture--hardening)
4. [Default Accounts & One-Click Autofill](#4-default-accounts--one-click-autofill)
5. [Local Setup Guide (XAMPP)](#5-local-setup-guide-xampp)
6. [Cloud / Live Hosting Deployment (InfinityFree)](#6-cloud--live-hosting-deployment-infinityfree)
7. [Automated Verification & Test Suites](#7-automated-verification--test-suites)
8. [Project File Architecture](#8-project-file-architecture)
9. [Author & Acknowledgments](#9-author--acknowledgments)

---

## 1. Key Features by Role

### 📚 Candidate / Student Role (`student_dashboard.php`)
- **Assessment Center**: Real-time performance statistics (Examinations Taken, Average Percentage, Highest Score, Available Quizzes).
- **Catalogue & Search**: Browse open course exams with prepared search by title and server-side pagination (`take_quiz.php`).
- **Examination Room**: Distraction-free testing environment with live progress tracking, question jump palette, and submit confirmation safeguards (`quiz_attempt.php`).
- **Authoritative Grading**: Real-time evaluation against database answer keys with session validation (`submit_quiz.php`).
- **Academic Transcript**: Historical test scores, percentage ranks, and personalized qualitative teacher feedback (`view_score.php`).

### 🎓 Faculty / Teacher Role (`teacher_dashboard.php`)
- **Assessment Management**: View authored examinations, question counts, and candidate submission tallies.
- **Exam Authoring**: Create new course assessments (`add_quiz.php`).
- **Question Bank Builder**: Add and edit multiple-choice questions with 3 answer options and an authoritative answer key (`add_question.php`, `edit_question.php`).
- **Strict IDOR Ownership Checks**: Teachers can only view, edit, or delete exams and questions belonging to their own account.
- **Candidate Evaluation**: Review student submissions and provide qualitative feedback (`view_results.php`).
- **Vetting Workflow**: Newly registered faculty accounts are placed in a `pending` state until vetted by an administrator (`signup_teacher.php`).

### 👑 Administrator Role (`admin_dashboard.php`)
- **Institutional Oversight**: Real-time audit metrics (Total Teachers, Pending Approvals, Total Students, Total Exams, Total Attempts).
- **Teacher Approval (Fully Operational)**: Review pending faculty applications and grant approval via CSRF-protected POST requests (`approve_teacher.php`).
- **Complete Directories with Search & Pagination**:
  - Faculty Directory (`admin_teachers.php`)
  - Student Registry (`admin_students.php`)
  - Examination Archive (`admin_quizzes.php`)
- **Policy Enforcement (Disabled Record Modification)**: In compliance with project security requirements, Admin record creation, editing, and deletion actions are visually disabled in the UI (with tooltips/badges such as `Deletion is currently disabled`) and strictly blocked on the server (`block_admin_action()`, returning HTTP 403 Forbidden with zero SQL execution).

---

## 2. Interactive Examination Experience

The examination room ([`quiz_attempt.php`](file:///f:/IUBAT/8th%20Semester/CSC%20387/quiz_project/quiz_attempt.php)) delivers an authentic digital testing hall:

- **Candidate Header Banner**: Displays candidate name, `#STU-XXXX` student ID, exam course title, faculty instructor name, total questions count, and institutional examination instructions.
- **Sticky Live Question Tracker Bar**:
  - Live progress display: `X of Y Answered, Z Remaining` and percentage indicator.
  - Visual completion bar that updates smoothly as questions are answered.
- **Question Jump Palette**: Numbered pills (`1` to `N`) that dynamically update color in real time as questions are answered and scroll smoothly to the target question when clicked.
- **Question Cards & Touch Targets**:
  - Distinct headers with question numbers and live `Answered` / `Unanswered` status badges.
  - 48px minimum touch target height for radio option labels with active and selected state highlighting.
- **Submission Confirmation Modal**:
  - Intercepts form submissions (button click and Enter key).
  - Displays a summary of answered vs. unanswered questions.
  - Shows advisory warnings for incomplete assessments.
  - Offers a choice between **Review Answers** (returns to exam) and **Confirm & Submit Examination**.

---

## 3. Security Architecture & Hardening

| Security Domain | Identified Risk | Solution Implemented |
|---|---|---|
| **SQL Injection (SQLi)** | Raw user input concatenated into SQL queries. | 100% converted to parameterized prepared statements (`$conn->prepare`, `bind_param`, `execute`). |
| **Cross-Site Request Forgery (CSRF)** | State-changing operations vulnerable to forgery. | Session-bound cryptographic tokens (`csrf_token`) validated on all POST endpoints (`require_csrf()`). |
| **Authentication & Session Fixation** | Stale session identifiers across privilege escalation. | Session ID regenerated upon login via `session_regenerate_id(true)` with secure cookie flags (`httponly`, `samesite=Lax`). |
| **Plaintext Password Storage** | Credentials stored unhashed in database. | Implemented `verify_and_rehash_password()` with transparent migration from plaintext to bcrypt upon login; all new signups hashed using `password_hash()`. |
| **Cross-Site Scripting (XSS)** | Dynamic variables echoed directly into HTML. | Applied context-aware output escaping via `e()` (`htmlspecialchars`) across all pages. |
| **Insecure Direct Object Reference (IDOR)** | Cross-teacher quiz or question tampering via URL manipulation. | Ownership enforcement joining `teacher_id` with active session before executing queries. |
| **Admin Action Restrictions** | Admins altering academic questions or records. | Server-side enforcement with `block_admin_action()` returning HTTP 403 Forbidden; client-side buttons genuinely disabled. |
| **Client-Side Grading Tampering** | Manipulating client evaluation scores. | Authoritative server-side grading querying the database directly (`submit_quiz.php`). |

---

## 4. Default Accounts & One-Click Autofill

The database comes pre-seeded with test accounts. All passwords support transparent legacy migration to bcrypt upon first login:

| Role | Email | Password | Status | Login Route |
|---|---|---|---|---|
| **Candidate / Student** | `r@gmail.com` | `123456` | Active | `student_login.php` |
| **Candidate / Student** | `labonno@gmail.com` | `123456` | Active | `student_login.php` |
| **Faculty / Teacher** | `rifat@gmail.com` | `123456` | Approved | `teacher_login.php` |
| **Faculty / Teacher** | `labonno@gmail.com` | `123456` | Approved | `teacher_login.php` |
| **Administrator** | `rifat5566123@gmail.com` | `123456` | Active | `admin_login.php` |
| **Administrator** | `labonno@gmail.com` | `123456` | Active | `admin_login.php` |

### Self-Contained Demo Credential Autofill
Every login page (`student_login.php`, `teacher_login.php`, `admin_login.php`) includes a dedicated **Demo Credentials** box with an **"Autofill Demo Credentials"** button.
- **Non-Submitting Safety**: Populates the email and password fields immediately without submitting the form, allowing review before signing in.
- **Self-Contained & Hosting-Proof**: Directly embedded with inline JavaScript fallbacks so it functions reliably on any host (including InfinityFree / `freedev.app`) without depending on external assets or environment constants.

---

## 5. Local Setup Guide (XAMPP)

### Step 1: Start Services
1. Open the **XAMPP Control Panel**.
2. Start **Apache** and **MySQL**.
3. Verify both modules display green status lights.

### Step 2: Import Database Schema
1. Open your browser and navigate to `http://localhost/phpmyadmin/`.
2. Create a new database named:
   ```text
   quiz_system
   ```
   *(Collation: `utf8mb4_general_ci`)*
3. Select `quiz_system` and click the **Import** tab.
4. Choose the SQL file located at:
   ```text
   SQL/quiz_system.sql
   ```
5. Click **Import** at the bottom of the page.

### Step 3: Run the Application
1. Place or clone the project folder into your XAMPP web root:
   ```text
   C:\xampp\htdocs\quiz_project
   ```
2. Open your web browser and navigate to:
   ```text
   http://localhost/quiz_project/
   ```

---

## 6. Cloud / Live Hosting Deployment (InfinityFree)

To deploy to InfinityFree or any LAMP/cPanel hosting:

1. **Upload Files**: Upload the project files to your hosting server's `htdocs/` directory.
2. **Database Import**:
   - Create a MySQL database in your hosting control panel.
   - Open phpMyAdmin on your hosting server and import `SQL/quiz_system.sql`.
3. **Database Configuration (`sql.php`)**:
   - Update `sql.php` with your remote database credentials:
     ```php
     $servername = "sqlXXX.epizy.com"; // Your hosting MySQL hostname
     $username   = "epiz_XXXXXXXX";     // Your hosting database username
     $password   = "your_password";     // Your hosting database password
     $dbname     = "epiz_XXXXXXXX_quiz_system";
     ```
4. **Live Autofill Compatibility**: The demo credential autofill buttons are self-contained and work on remote hosting without configuration changes.

---

## 7. Automated Verification & Test Suites

The project includes automated verification test scripts:

```powershell
python check_syntax.py
python test_suite.py
```

### Test Results Summary:
- **`check_syntax.py`**: **34/34 PHP files verified** with balanced brackets, quotes, and valid lexical delimiters.
- **`test_suite.py`**: **126/126 automated tests passed** across 12 test suites:
  - *Suite 1*: Syntax & Delimiter Integrity (34 tests)
  - *Suite 2*: SQL Injection Static Audit (0 raw concatenation calls)
  - *Suite 3*: Admin Permissions & Disabled Action Guards (12 tests)
  - *Suite 4*: Teacher Approval Workflow (6 tests)
  - *Suite 5*: Authentication Guards & CSRF Token Verification (20 tests)
  - *Suite 6*: Server-Side Quiz Scoring Integrity (3 tests)
  - *Suite 7*: Password Migration & Bcrypt Rehash (3 tests)
  - *Suite 8*: Explore Quizzes Search & "See More" Pagination (6 tests)
  - *Suite 9*: Admin "See More" Directory Pages & Disabled Action Flags (14 tests)
  - *Suite 10*: Footer Links & Navbar Brand Identity (6 tests)
  - *Suite 11*: Demo Credentials & Autofill Handler (7 tests)
  - *Suite 12*: University Examination Portal & Exam Room Experience (11 tests)

---

## 8. Project File Architecture

```text
quiz_project/
├── SQL/
│   └── quiz_system.sql          # Database schema dump and default seed data
├── assets/
│   ├── css/
│   │   └── style.css            # Responsive academic design system (320px–1440px+)
│   └── js/
│       └── main.js              # Exam room tracker, palette scroll, mobile menu, autofill
├── includes/
│   ├── auth.php                 # Role guards, session utilities, demo credential helpers
│   ├── csrf.php                 # Cryptographic CSRF token generation and validation
│   ├── db.php                   # Database connection bridge
│   ├── footer.php               # Universal footer with copyright and external links
│   ├── header.php               # Universal navbar with role pills and mobile drawer
│   └── security.php             # Password rehashing, XSS sanitization, admin action blockers
├── index.php                    # Examination portal landing page
├── student_login.php            # Candidate login with Autofill Demo Credentials
├── teacher_login.php            # Faculty login with Autofill Demo Credentials
├── admin_login.php              # Administrator login with Autofill Demo Credentials
├── signup_student.php           # Candidate registration
├── signup_teacher.php           # Faculty registration (creates pending account)
├── logout.php                   # Secure session termination
├── student_dashboard.php        # Candidate assessment center & exam previews
├── take_quiz.php                # Exam catalog with search & pagination
├── quiz_attempt.php             # Examination room (banner, tracker, palette, modal)
├── submit_quiz.php              # Authoritative server-side evaluation & score recording
├── view_score.php               # Candidate historical academic transcript
├── teacher_dashboard.php        # Faculty assessment management workspace
├── add_quiz.php                 # Create examination endpoint
├── add_question.php             # Add question with answer options and key
├── edit_question.php            # Update question text, options, or answer key
├── delete_quiz.php              # Secure exam deletion (teacher only)
├── delete_question.php          # Secure question deletion (teacher only)
├── view_quiz.php                # Read-only exam and question inspector
├── view_results.php             # Student submission grades and feedback authoring
├── admin_dashboard.php          # Administrator monitoring & approval overview
├── admin_teachers.php           # Full faculty directory with search & approval action
├── admin_students.php           # Full student directory with search
├── admin_quizzes.php            # Full exam directory with search & read-only links
├── approve_teacher.php          # Vets and approves pending faculty accounts
├── delete_teacher.php           # Blocked deletion endpoint (HTTP 403 Forbidden)
├── delete_student.php           # Blocked deletion endpoint (HTTP 403 Forbidden)
├── sql.php                      # Database connection configuration
├── check_syntax.py              # Automated PHP syntax checker
├── test_suite.py                # Comprehensive 126-test verification suite
├── IMPLEMENTATION_PLAN.md       # Implementation architecture and audit report
└── README.md                    # Project documentation
```

---

## 9. Author & Acknowledgments

- **Developer:** Rifat Bin Alam
- **Live Deployment:** [quiz-management-system.freedev.app](https://quiz-management-system.freedev.app/)
- **GitHub:** [Rifat-Bin-Alam](https://github.com/Rifat-Bin-Alam)
- **Portfolio:** [rifat-bin-alam.vercel.app](https://rifat-bin-alam.vercel.app/)
- **Academic Coursework:** CSC 387 — Web Engineering / Software Project

*Copyright © 2026 Rifat Bin Alam. All rights reserved.*

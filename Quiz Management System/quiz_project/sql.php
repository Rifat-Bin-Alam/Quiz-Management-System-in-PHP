<?php
// sql.php - Database connection setup for XAMPP (Apache + MySQL/MariaDB)

$servername = "localhost";
$username = "root";      // Default XAMPP username
$password = "";          // Default XAMPP password
$dbname = "quiz_system";

// Create connection only if not already established
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_errno) {
    // Disable mysqli reporting full traces to browser for security
    mysqli_report(MYSQLI_REPORT_OFF);

    $conn = @new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        // Safe, clean error message without exposing credentials or internal paths
        die("<div style='font-family:system-ui,sans-serif;max-width:600px;margin:50px auto;padding:24px;border:1px solid #fecaca;background:#fef2f2;border-radius:8px;color:#991b1b;'>"
            . "<h2 style='margin-top:0;'>Database Connection Error</h2>"
            . "<p>Unable to connect to the database <code>quiz_system</code>. Please verify that MySQL is started in your XAMPP Control Panel and the database has been imported.</p>"
            . "</div>");
    }

    // Set UTF-8 encoding
    $conn->set_charset("utf8mb4");

    // Environment configuration: 'development' or 'production'
    if (!defined('APP_ENV')) {
        define('APP_ENV', 'development');
    }

    // In development mode, ensure demo student test account exists in local database
    if (APP_ENV === 'development') {
        $chk_labonno = @$conn->query("SELECT student_id FROM student WHERE email = 'labonno@gmail.com' LIMIT 1");
        if ($chk_labonno && $chk_labonno->num_rows === 0) {
            @$conn->query("INSERT INTO student (s_name, email, password, phone_no) VALUES ('Farhana Karim Labonno', 'labonno@gmail.com', '123456', '0123')");
        }
    }
}
?>

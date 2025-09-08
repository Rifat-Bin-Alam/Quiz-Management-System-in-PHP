<?php
require_once 'sql.php';
$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$student_id = $_GET['id'];

// 1. Delete scores of this student
mysqli_query($conn, "DELETE FROM score WHERE student_id='$student_id'");

// 2. Delete student
if (mysqli_query($conn, "DELETE FROM student WHERE student_id='$student_id'")) {
    header("Location: admin_dashboard.php?msg=student_deleted");
    exit();
} else {
    echo "Error deleting student: " . mysqli_error($conn);
}
?>

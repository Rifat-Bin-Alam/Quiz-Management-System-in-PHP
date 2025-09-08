<?php
require_once 'sql.php';
$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$teacher_id = $_GET['id'];

// 1. Delete scores for quizzes created by this teacher
mysqli_query($conn, "DELETE FROM score WHERE quiz_id IN (SELECT quiz_id FROM quiz WHERE teacher_id='$teacher_id')");

// 2. Delete questions for this teacher’s quizzes
mysqli_query($conn, "DELETE FROM question WHERE quiz_id IN (SELECT quiz_id FROM quiz WHERE teacher_id='$teacher_id')");

// 3. Delete the teacher’s quizzes
mysqli_query($conn, "DELETE FROM quiz WHERE teacher_id='$teacher_id'");

// 4. Delete the teacher
if (mysqli_query($conn, "DELETE FROM teacher WHERE teacher_id='$teacher_id'")) {
    header("Location: admin_dashboard.php?msg=teacher_deleted");
    exit();
} else {
    echo "Error deleting teacher: " . mysqli_error($conn);
}
?>

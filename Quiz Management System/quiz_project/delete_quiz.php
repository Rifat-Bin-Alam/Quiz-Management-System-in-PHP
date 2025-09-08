<?php
session_start();
require_once 'sql.php';
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) die("Connection failed: " . mysqli_connect_error());

// Teacher session check
if(!isset($_SESSION['teacher_id'])){
    header("Location: teacher_login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// Check if quiz ID is provided
if(!isset($_GET['qid'])){
    die("Invalid request.");
}
$quiz_id = $_GET['qid'];

// Verify the teacher owns this quiz
$sqlCheck = "SELECT * FROM quiz WHERE quiz_id='$quiz_id' AND teacher_id='$teacher_id'";
$resCheck = mysqli_query($conn, $sqlCheck);
if(mysqli_num_rows($resCheck) == 0){
    die("Quiz not found or access denied.");
}

// Delete related questions first (foreign key safety)
mysqli_query($conn, "DELETE FROM question WHERE quiz_id='$quiz_id'");

// Delete student scores related to this quiz
mysqli_query($conn, "DELETE FROM score WHERE quiz_id='$quiz_id'");

// Delete the quiz itself
mysqli_query($conn, "DELETE FROM quiz WHERE quiz_id='$quiz_id'");

// Redirect back with message
header("Location: teacher_dashboard.php?deleted=1");
exit();
?>

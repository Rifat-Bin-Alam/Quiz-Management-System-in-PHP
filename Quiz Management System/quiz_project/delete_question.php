<?php
session_start();
require_once 'sql.php';
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

// Teacher login check
if(!isset($_SESSION['teacher_id'])){
    header("Location: teacher_login.php");
    exit();
}

$question_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get quiz_id before deleting
$sql = "SELECT quiz_id FROM question WHERE question_id='$question_id'";
$res = mysqli_query($conn, $sql);
if(mysqli_num_rows($res) > 0){
    $row = mysqli_fetch_assoc($res);
    $quiz_id = $row['quiz_id'];
    mysqli_query($conn, "DELETE FROM question WHERE question_id='$question_id'");
}

header("Location: view_quiz.php?qid=$quiz_id");
exit();
?>

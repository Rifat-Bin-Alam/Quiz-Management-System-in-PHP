<?php
session_start();
require_once 'sql.php';

$conn = mysqli_connect($servername, $username, $password, $dbname);
if(!$conn) die("Connection failed: ".mysqli_connect_error());

if(!isset($_SESSION['student_id'])){
    header("Location: student_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $quiz_id = $_POST['quiz_id'];
    $answers = $_POST['answer']; // associative array: question_id => selected_option

    $total_questions = count($answers);
    $correct_count = 0;

    foreach($answers as $question_id => $selected){
        $sql = "SELECT correct_answer FROM question WHERE question_id='$question_id'";
        $res = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($res);
        if($row['correct_answer'] == $selected){
            $correct_count++;
        }
    }

    // Insert or update score
    $sql = "INSERT INTO score(student_id, quiz_id, score_point, total_point)
            VALUES('$student_id','$quiz_id','$correct_count','$total_questions')
            ON DUPLICATE KEY UPDATE score_point='$correct_count', total_point='$total_questions'";
    mysqli_query($conn, $sql);

    // Redirect to dashboard with a success message
    header("Location: student_dashboard.php?msg=Quiz submitted successfully");
    exit();
}
?>

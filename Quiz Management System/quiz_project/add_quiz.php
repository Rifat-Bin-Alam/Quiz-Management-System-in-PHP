<?php
session_start();
require_once 'sql.php';
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

// Check teacher login
if(!isset($_SESSION['teacher_id'])){
    header("Location: teacher_login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'];
$message = '';

if(isset($_POST['submit'])){
    $quiz_name = mysqli_real_escape_string($conn, $_POST['quiz_name']);
    if($quiz_name != ''){
        $sql = "INSERT INTO quiz (teacher_id, quiz_name) VALUES ('$teacher_id', '$quiz_name')";
        if(mysqli_query($conn, $sql)){
            $message = "Quiz created successfully!";
        } else {
            $message = "Error: ".mysqli_error($conn);
        }
    } else {
        $message = "Please enter quiz name";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Quiz - Quiz System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{ font-family: Arial,sans-serif; background:#f2f2f2; margin:0; padding:0;}
        .header{ background:#042A38; color:white; padding:1rem; text-align:center;}
        .container{ width:90%; max-width:500px; margin:2rem auto; background:white; padding:1.5rem; border-radius:8px;}
        input[type=text]{ width:95%; padding:0.5rem; margin:0.5rem 0; border-radius:5px; border:1px solid #ccc; display:block; margin-left:auto; margin-right:auto;}
        button{ width:100%; padding:0.7rem; background:lightblue; border:none; border-radius:5px; cursor:pointer; font-weight:bold; }
        .message{ color:green; text-align:center; margin:0.5rem 0; }
        a{ display:block; text-align:center; margin-top:0.5rem; text-decoration:none; color:#042A38; }
    </style>
</head>
<body>
<div class="header"><h1>Add New Quiz</h1></div>
<div class="container">
    <?php if($message != '') echo "<p class='message'>$message</p>"; ?>
    <form method="POST">
        <input type="text" name="quiz_name" placeholder="Enter Quiz Name" required>
        <button type="submit" name="submit">Create Quiz</button>
    </form>
    <a href="teacher_dashboard.php">Back to Dashboard</a>
</div>
</body>
</html>

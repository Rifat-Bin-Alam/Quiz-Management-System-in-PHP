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
$teacher_name = $_SESSION['teacher_name'];

// Get quiz_id from URL
if(!isset($_GET['qid'])){
    echo "Invalid Quiz.";
    exit();
}
$quiz_id = $_GET['qid'];

// Fetch quiz info to confirm teacher owns it
$sqlQuiz = "SELECT * FROM quiz WHERE quiz_id='$quiz_id' AND teacher_id='$teacher_id'";
$resQuiz = mysqli_query($conn, $sqlQuiz);
if(mysqli_num_rows($resQuiz) == 0){
    echo "Quiz not found or access denied.";
    exit();
}
$quiz = mysqli_fetch_assoc($resQuiz);

// Handle feedback submission
if(isset($_POST['submit_feedback'])){
    $score_id = $_POST['score_id'];
    $feedback = mysqli_real_escape_string($conn, $_POST['feedback']);
    mysqli_query($conn, "UPDATE score SET feedback='$feedback' WHERE score_id='$score_id'");
    $message = "Feedback updated successfully!";
}

// Fetch all student results for this quiz
$sql = "SELECT s.score_id, st.student_id, st.s_name, s.score_point, s.total_point, s.feedback 
        FROM score s
        JOIN student st ON s.student_id = st.student_id
        WHERE s.quiz_id='$quiz_id'";
$res = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Results for <?php echo $quiz['quiz_name']; ?> - Quiz System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background:#f2f2f2; margin:0; padding:0; }
        .header { background:#042A38; color:white; padding:1rem; display:flex; justify-content:space-between; align-items:center; }
        .container { width:95%; margin:2rem auto; background:white; padding:1rem; border-radius:8px; }
        table { width:100%; border-collapse:collapse; margin-top:1rem; }
        th, td { border:1px solid #ddd; padding:0.5rem; text-align:center; }
        th { background:#042A38; color:white; }
        button { padding:0.4rem 0.8rem; border:none; border-radius:5px; cursor:pointer; background:#042A38; color:white; }
        .feedback-input { width:90%; padding:0.3rem; border-radius:5px; }
        .message { color:green; font-weight:bold; text-align:center; }
        a { text-decoration:none; color:white; }
        .dashboard-btn { padding:0.5rem 1rem; background:#f39c12; color:white; border-radius:5px; text-decoration:none; }
        .btn-container { text-align:right; margin-bottom:1rem; }
    </style>
</head>
<body>

<div class="header">
    <h1><?php echo $teacher_name; ?> - Quiz: <?php echo $quiz['quiz_name']; ?></h1>
</div>

<div class="container">
    <!-- Go Back Button -->
    <div class="btn-container">
        <a href="teacher_dashboard.php" class="dashboard-btn">Go Back to Dashboard</a>
    </div>

    <?php if(isset($message)) echo "<p class='message'>$message</p>"; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Student Name</th>
                <th>Score</th>
                <th>Total</th>
                <th>Feedback</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if(mysqli_num_rows($res) > 0){
                while($row = mysqli_fetch_assoc($res)){
                    echo "<tr>";
                    echo "<td>".$row['student_id']."</td>";
                    echo "<td>".$row['s_name']."</td>";
                    echo "<td>".$row['score_point']."</td>";
                    echo "<td>".$row['total_point']."</td>";
                    echo "<td>
                        <form method='POST'>
                            <input type='hidden' name='score_id' value='".$row['score_id']."'>
                            <input type='text' class='feedback-input' name='feedback' value='".$row['feedback']."'>
                    </td>";
                    echo "<td><button type='submit' name='submit_feedback'>Save</button></form></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No students have taken this quiz yet.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>

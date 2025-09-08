<?php
session_start();
require_once 'sql.php';

// Connect to DB
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) die("Connection failed: " . mysqli_connect_error());

// Check if student is logged in
if(!isset($_SESSION['student_id'])){
    header("Location: student_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

// Get quiz ID
if(!isset($_GET['qid'])){
    die("Invalid quiz.");
}
$quiz_id = $_GET['qid'];

// Fetch quiz info
$quiz_sql = "SELECT quiz_name FROM quiz WHERE quiz_id='$quiz_id'";
$quiz_res = mysqli_query($conn, $quiz_sql);
if(mysqli_num_rows($quiz_res) == 0){
    die("Quiz not found.");
}
$quiz_row = mysqli_fetch_assoc($quiz_res);
$quiz_name = $quiz_row['quiz_name'];

// Fetch questions
$q_sql = "SELECT * FROM question WHERE quiz_id='$quiz_id'";
$q_res = mysqli_query($conn, $q_sql);
if(mysqli_num_rows($q_res) == 0){
    die("No questions found for this quiz.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $quiz_name; ?> - Take Quiz</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial,sans-serif; background:#f2f2f2; margin:0; padding:0; }
        .header { background:#042A38; color:white; padding:1rem; display:flex; justify-content:space-between; align-items:center; }
        .container { width:95%; margin:2rem auto; background:white; padding:1rem; border-radius:8px; }
        h2 { text-align:center; color:#042A38; }
        .question { margin-bottom:1rem; }
        button { padding:0.5rem 1rem; background:lightblue; color:#042A38; border:none; border-radius:5px; cursor:pointer; }
        a.logout { color:white; text-decoration:none; font-weight:bold; border:1px solid white; padding:0.3rem 0.5rem; border-radius:5px; }
    </style>
</head>
<body>
<div class="header">
    <h1><?php echo $quiz_name; ?></h1>
    <a href="student_dashboard.php" class="logout">Back</a>
</div>

<div class="container">
    <form method="POST" action="submit_quiz.php">
        <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
        <?php
        $q_num = 1;
        while($row = mysqli_fetch_assoc($q_res)){
            echo "<div class='question'>";
            echo "<p>".$q_num.". ".$row['question_text']."</p>";
            // Radio buttons for options
            for($i=1; $i<=3; $i++){
                $opt = $row["option".$i];
                echo "<label><input type='radio' name='answer[".$row['question_id']."]' value='".$opt."' required> ".$opt."</label><br>";
            }
            echo "</div>";
            $q_num++;
        }
        ?>
        <button type="submit">Submit Quiz</button>
    </form>
</div>
</body>
</html>

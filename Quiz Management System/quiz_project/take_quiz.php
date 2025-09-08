<?php
session_start();
require_once 'sql.php';
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

if(!isset($_SESSION['student_id'])){
    header("Location: student_login.php");
    exit();
}

// Logout
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: index.php");
    exit();
}

// Fetch student info
$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard - Quiz System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; margin:0; padding:0; background:#f2f2f2; }
        .header { background:#042A38; color:white; padding:1rem; display:flex; justify-content:space-between; align-items:center; }
        .container { width:95%; margin:2rem auto; background:white; padding:1rem; border-radius:8px; }
        h2 { text-align:center; color:#042A38; margin-bottom:1rem; }
        table { width:100%; border-collapse:collapse; margin-top:1rem; }
        th, td { border:1px solid #ddd; padding:0.5rem; text-align:center; }
        th { background:#042A38; color:white; }
        a.logout, a.dashboard { color:white; text-decoration:none; font-weight:bold; border:1px solid white; padding:0.3rem 0.5rem; border-radius:5px; margin-right:0.5rem; }
        a.dashboard { background:#042A38; }
        button { padding:0.4rem 0.8rem; background-color:lightblue; color:#042A38; border:none; border-radius:5px; cursor:pointer; }
        .btn-link { text-decoration:none; }
    </style>
</head>
<body>

<div class="header">
    <h1>Welcome, <?php echo $student_name; ?></h1>
    <div>
        <a href="student_dashboard.php" class="dashboard">Dashboard</a>
        <a href="?logout=true" class="logout">Logout</a>
    </div>
</div>

<div class="container">
    <h2>Available Quizzes</h2>
    <table>
        <thead>
            <tr>
                <th>Quiz Name</th>
                <th>Teacher</th>
                <th>Date Created</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Fetch all quizzes
        $sql = "SELECT q.quiz_id, q.quiz_name, q.date_created, t.t_name 
                FROM quiz q 
                JOIN teacher t ON q.teacher_id = t.teacher_id";
        $res = mysqli_query($conn, $sql);
        if($res){
            if(mysqli_num_rows($res) > 0){
                while($row = mysqli_fetch_assoc($res)){
                    echo "<tr>";
                    echo "<td>".$row['quiz_name']."</td>";
                    echo "<td>".$row['t_name']."</td>";
                    echo "<td>".$row['date_created']."</td>";
                    echo "<td>
                            <a class='btn-link' href='quiz_attempt.php?qid=".$row['quiz_id']."'>
                                <button>Take Quiz</button>
                            </a>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No quizzes available at the moment</td></tr>";
            }
        }
        ?>
        </tbody>
    </table>
</div>

<div class="container">
    <h2>Your Scores</h2>
    <table>
        <thead>
            <tr>
                <th>Quiz Name</th>
                <th>Score</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT q.quiz_name, s.score_point, s.total_point 
                FROM score s 
                JOIN quiz q ON s.quiz_id = q.quiz_id
                WHERE s.student_id='$student_id'";
        $res = mysqli_query($conn, $sql);
        if($res){
            if(mysqli_num_rows($res) > 0){
                while($row = mysqli_fetch_assoc($res)){
                    echo "<tr>";
                    echo "<td>".$row['quiz_name']."</td>";
                    echo "<td>".$row['score_point']."</td>";
                    echo "<td>".$row['total_point']."</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='3'>No scores available</td></tr>";
            }
        }
        ?>
        </tbody>
    </table>
</div>

</body>
</html>

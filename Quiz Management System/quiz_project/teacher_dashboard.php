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

// Logout
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: index.php");
    exit();
}

// Success message after deleting a quiz
$deleted_message = "";
if(isset($_GET['deleted'])){
    $deleted_message = "Quiz deleted successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teacher Dashboard - Quiz System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; margin:0; padding:0; background:#f2f2f2; }
        .header { background:#042A38; color:white; padding:1rem; display:flex; justify-content:space-between; align-items:center; }
        .container { width:95%; margin:2rem auto; background:white; padding:1rem; border-radius:8px; }
        h2 { text-align:center; color:#042A38; }
        table { width:100%; border-collapse:collapse; margin-top:1rem; }
        th, td { border:1px solid #ddd; padding:0.5rem; text-align:center; }
        th { background:#042A38; color:white; }
        a.logout { color:white; text-decoration:none; font-weight:bold; border:1px solid white; padding:0.3rem 0.5rem; border-radius:5px; }
        button { padding:0.4rem 0.8rem; background-color:lightblue; color:#042A38; border:none; border-radius:5px; cursor:pointer; margin:2px; }
        .delete-btn { background-color:red; color:white; }
        .message { color:green; text-align:center; font-weight:bold; }
    </style>
</head>
<body>

<div class="header">
    <h1>Welcome, <?php echo $teacher_name; ?></h1>
    <a href="?logout=true" class="logout">Logout</a>
</div>

<div class="container">
    <?php if($deleted_message) echo "<p class='message'>$deleted_message</p>"; ?>
    <h2>Your Quizzes</h2>
    <a href="add_quiz.php"><button>Add New Quiz</button></a>
    <table>
        <thead>
            <tr>
                <th>Quiz Name</th>
                <th>Date Created</th>
                <th>Students Taken</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Fetch all quizzes for this teacher
        $sql = "SELECT * FROM quiz WHERE teacher_id='$teacher_id'";
        $res = mysqli_query($conn, $sql);
        if($res){
            if(mysqli_num_rows($res) > 0){
                while($quiz = mysqli_fetch_assoc($res)){
                    $quiz_id = $quiz['quiz_id'];

                    // Count how many students have taken this quiz
                    $count_res = mysqli_query($conn, "SELECT COUNT(*) as count FROM score WHERE quiz_id='$quiz_id'");
                    $count_row = mysqli_fetch_assoc($count_res);
                    $students_taken = $count_row['count'];

                    echo "<tr>";
                    echo "<td>".$quiz['quiz_name']."</td>";
                    echo "<td>".$quiz['date_created']."</td>";
                    echo "<td>".$students_taken."</td>";
                    echo "<td>
                        <a href='view_quiz.php?qid=$quiz_id'><button>View</button></a>
                        <a href='add_question.php?qid=$quiz_id'><button>Add Question</button></a>
                        <a href='view_results.php?qid=$quiz_id'><button>View Results</button></a>
                        <a href='delete_quiz.php?qid=$quiz_id' onclick=\"return confirm('Are you sure you want to delete this quiz?');\">
                            <button class='delete-btn'>Delete</button>
                        </a>
                    </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No quizzes found</td></tr>";
            }
        } else {
            echo "<tr><td colspan='4'>Error fetching quizzes</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

</body>
</html>

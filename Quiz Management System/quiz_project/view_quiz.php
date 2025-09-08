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

$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'];
$quiz_id = isset($_GET['qid']) ? intval($_GET['qid']) : 0;

?>

<!DOCTYPE html>
<html>
<head>
    <title>View Quiz - Quiz System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{ font-family: Arial,sans-serif; background:#f2f2f2; margin:0; padding:0;}
        .header{ background:#042A38; color:white; padding:1rem; text-align:center;}
        .container{ width:95%; margin:2rem auto; background:white; padding:1rem; border-radius:8px;}
        table{ width:100%; border-collapse:collapse; }
        th, td{ border:1px solid #ddd; padding:0.5rem; text-align:center;}
        th{ background:#042A38; color:white;}
        button{ padding:0.3rem 0.7rem; border:none; border-radius:5px; cursor:pointer; background:lightblue; margin:0.2rem;}
        a{ text-decoration:none; color:#042A38; }
    </style>
</head>
<body>
<div class="header"><h1>Quiz Questions</h1></div>
<div class="container">
    <a href="add_question.php?qid=<?php echo $quiz_id; ?>"><button>Add New Question</button></a>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Question</th>
                <th>Option1</th>
                <th>Option2</th>
                <th>Option3</th>
                <th>Answer</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM question WHERE quiz_id='$quiz_id'";
            $res = mysqli_query($conn, $sql);
            $i=1;
            if($res && mysqli_num_rows($res)>0){
                while($row = mysqli_fetch_assoc($res)){
                    echo "<tr>";
                    echo "<td>".$i."</td>";
                    echo "<td>".$row['question_text']."</td>";
                    echo "<td>".$row['option1']."</td>";
                    echo "<td>".$row['option2']."</td>";
                    echo "<td>".$row['option3']."</td>";
                    echo "<td>".$row['correct_answer']."</td>";
                    echo "<td>
                        <a href='edit_question.php?id=".$row['question_id']."'><button>Edit</button></a>
                        <a href='delete_question.php?id=".$row['question_id']."'><button style='background:red;color:white;'>Delete</button></a>
                    </td>";
                    echo "</tr>";
                    $i++;
                }
            } else {
                echo "<tr><td colspan='7'>No questions added yet</td></tr>";
            }
            ?>
        </tbody>
    </table>
    <a href="teacher_dashboard.php">Back to Dashboard</a>
</div>
</body>
</html>

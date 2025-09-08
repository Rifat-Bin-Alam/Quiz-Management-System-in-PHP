<?php
session_start();
require_once 'sql.php';
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

if(!isset($_SESSION['teacher_id'])){
    header("Location: teacher_login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$quiz_id = isset($_GET['qid']) ? intval($_GET['qid']) : 0;
$message = '';

if(isset($_POST['submit'])){
    $question_text = mysqli_real_escape_string($conn, $_POST['question_text']);
    $option1 = mysqli_real_escape_string($conn, $_POST['option1']);
    $option2 = mysqli_real_escape_string($conn, $_POST['option2']);
    $option3 = mysqli_real_escape_string($conn, $_POST['option3']);
    $correct_answer = isset($_POST['correct_answer']) ? mysqli_real_escape_string($conn, $_POST['correct_answer']) : '';

    if($question_text && $option1 && $option2 && $option3 && $correct_answer){
        $sql = "INSERT INTO question (quiz_id, question_text, option1, option2, option3, correct_answer) 
                VALUES ('$quiz_id','$question_text','$option1','$option2','$option3','$correct_answer')";
        if(mysqli_query($conn, $sql)){
            $message = "Question added successfully!";
        } else {
            $message = "Error: ".mysqli_error($conn);
        }
    } else {
        $message = "Please fill all fields and select a correct answer!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Question - Quiz System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{ font-family: Arial,sans-serif; background:#f2f2f2; margin:0; padding:0;}
        .header{ background:#042A38; color:white; padding:1rem; text-align:center;}
        .container{ width:90%; max-width:500px; margin:2rem auto; background:white; padding:1.5rem; border-radius:8px;}
        input[type=text]{ width:95%; padding:0.5rem; margin:0.5rem 0; border-radius:5px; border:1px solid #ccc; display:block; margin-left:auto; margin-right:auto;}
        button{ width:100%; padding:0.7rem; background:lightblue; border:none; border-radius:5px; cursor:pointer; font-weight:bold; margin-top:0.5rem;}
        .message{ color:red; text-align:center; margin-bottom:0.5rem; }
        .options{ margin:0.5rem 0; }
        label{ margin-right:1rem; }
        a{ display:block; text-align:center; margin-top:0.5rem; text-decoration:none; color:#042A38; }
    </style>
</head>
<body>
<div class="header"><h1>Add New Question</h1></div>
<div class="container">
    <?php if($message != '') echo "<p class='message'>$message</p>"; ?>
    <form method="POST">
        <input type="text" name="question_text" placeholder="Question Text" required>
        <input type="text" name="option1" placeholder="Option 1" required>
        <input type="text" name="option2" placeholder="Option 2" required>
        <input type="text" name="option3" placeholder="Option 3" required>

        <div class="options">
            <p>Select Correct Answer:</p>
            <label><input type="radio" name="correct_answer" value="" id="r1" required> Option 1</label>
            <label><input type="radio" name="correct_answer" value="" id="r2"> Option 2</label>
            <label><input type="radio" name="correct_answer" value="" id="r3"> Option 3</label>
        </div>

        <button type="submit" name="submit">Add Question</button>
    </form>
    <a href="view_quiz.php?qid=<?php echo $quiz_id; ?>">Back to Quiz</a>
</div>

<script>
// Dynamically set the radio button values to match options
const r1 = document.getElementById('r1');
const r2 = document.getElementById('r2');
const r3 = document.getElementById('r3');
const inputs = document.querySelectorAll('input[type=text]');

function updateRadioValues(){
    r1.value = inputs[1].value;
    r2.value = inputs[2].value;
    r3.value = inputs[3].value;
}

inputs.forEach(input => {
    input.addEventListener('input', updateRadioValues);
});

updateRadioValues(); // Initialize on page load
</script>
</body>
</html>

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
$message = '';

// Fetch existing question
$sql = "SELECT * FROM question WHERE question_id='$question_id'";
$res = mysqli_query($conn, $sql);
if(mysqli_num_rows($res) == 0){
    die("Question not found!");
}
$row = mysqli_fetch_assoc($res);
$quiz_id = $row['quiz_id'];

// Update question
if(isset($_POST['submit'])){
    $question_text = mysqli_real_escape_string($conn, $_POST['question_text']);
    $option1 = mysqli_real_escape_string($conn, $_POST['option1']);
    $option2 = mysqli_real_escape_string($conn, $_POST['option2']);
    $option3 = mysqli_real_escape_string($conn, $_POST['option3']);
    $correct_answer = isset($_POST['correct_answer']) ? mysqli_real_escape_string($conn, $_POST['correct_answer']) : '';

    if($question_text && $option1 && $option2 && $option3 && $correct_answer){
        $sql = "UPDATE question SET question_text='$question_text', option1='$option1', option2='$option2', option3='$option3', correct_answer='$correct_answer' 
                WHERE question_id='$question_id'";
        if(mysqli_query($conn, $sql)){
            $message = "Question updated successfully!";
            $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM question WHERE question_id='$question_id'"));
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
    <title>Edit Question - Quiz System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{ font-family: Arial,sans-serif; background:#f2f2f2; margin:0; padding:0;}
        .header{ background:#042A38; color:white; padding:1rem; text-align:center;}
        .container{ width:90%; max-width:500px; margin:2rem auto; background:white; padding:1.5rem; border-radius:8px;}
        input[type=text]{ width:95%; padding:0.5rem; margin:0.5rem 0; border-radius:5px; border:1px solid #ccc; display:block; margin-left:auto; margin-right:auto;}
        button{ width:100%; padding:0.7rem; background:lightblue; border:none; border-radius:5px; cursor:pointer; font-weight:bold; margin-top:0.5rem;}
        .message{ color:green; text-align:center; margin-bottom:0.5rem; }
        .options{ margin:0.5rem 0; }
        label{ margin-right:1rem; }
        a{ display:block; text-align:center; margin-top:0.5rem; text-decoration:none; color:#042A38; }
    </style>
</head>
<body>
<div class="header"><h1>Edit Question</h1></div>
<div class="container">
    <?php if($message != '') echo "<p class='message'>$message</p>"; ?>
    <form method="POST">
        <input type="text" name="question_text" value="<?php echo $row['question_text']; ?>" required>
        <input type="text" name="option1" value="<?php echo $row['option1']; ?>" required>
        <input type="text" name="option2" value="<?php echo $row['option2']; ?>" required>
        <input type="text" name="option3" value="<?php echo $row['option3']; ?>" required>

        <div class="options">
            <p>Select Correct Answer:</p>
            <label><input type="radio" name="correct_answer" value="" id="r1" required> Option 1</label>
            <label><input type="radio" name="correct_answer" value="" id="r2"> Option 2</label>
            <label><input type="radio" name="correct_answer" value="" id="r3"> Option 3</label>
        </div>

        <button type="submit" name="submit">Update Question</button>
    </form>
    <a href="view_quiz.php?qid=<?php echo $quiz_id; ?>">Back to Quiz</a>
</div>

<script>
// Dynamically set radio button values based on the current options
const r1 = document.getElementById('r1');
const r2 = document.getElementById('r2');
const r3 = document.getElementById('r3');
const inputs = document.querySelectorAll('input[type=text]');

function updateRadioValues(){
    r1.value = inputs[1].value;
    r2.value = inputs[2].value;
    r3.value = inputs[3].value;
}

// Initialize radio button values
updateRadioValues();

// Pre-select the correct answer
if("<?php echo $row['correct_answer']; ?>" === inputs[1].value) r1.checked = true;
else if("<?php echo $row['correct_answer']; ?>" === inputs[2].value) r2.checked = true;
else if("<?php echo $row['correct_answer']; ?>" === inputs[3].value) r3.checked = true;

// Update radio values if options are changed
inputs.forEach(input => {
    input.addEventListener('input', updateRadioValues);
});
</script>
</body>
</html>

<?php
require_once 'sql.php';
$conn = mysqli_connect($servername, $username, $password, $dbname);

$message = "";

if(!$conn){
    die("Database connection failed: " . mysqli_connect_error());
}

if(isset($_POST['submit'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];
    $phone = $_POST['phone'];

    if($password !== $cpassword){
        $message = "<p style='color:red;'>Passwords do not match!</p>";
    } else {
        $check = mysqli_query($conn, "SELECT * FROM student WHERE email='$email'");
        if(mysqli_num_rows($check) > 0){
            $message = "<p style='color:red;'>Email already registered!</p>";
        } else {
            $sql = "INSERT INTO student (s_name,email,password,phone_no) VALUES ('$name','$email','$password','$phone')";
            if(mysqli_query($conn,$sql)){
                $message = "<p style='color:green;'>Registration Successful! You can now <a href='student_login.php'>login</a>.</p>";
            } else {
                $message = "<p style='color:red;'>Error: ".mysqli_error($conn)."</p>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Registration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{font-family: Arial; background:#042A38; color:#fff; display:flex; justify-content:center; align-items:center; height:100vh;}
        .container{background:#fff; color:#042A38; padding:30px; border-radius:10px; width:300px;}
        input[type=text], input[type=email], input[type=password], input[type=tel]{width:100%; padding:8px; margin:6px 0; border-radius:5px; border:1px solid #ccc;}
        input[type=submit]{width:100%; padding:10px; background:lightblue; border:none; border-radius:5px; font-weight:bold; cursor:pointer;}
        a{color:#042A38; text-decoration:none; font-size:0.9rem;}
        .show-pass{font-size:0.8rem;}
        .message{margin:10px 0; font-weight:bold;}
    </style>
</head>
<body>
<div class="container">
    <h2>Student Registration</h2>
    <?php if($message) echo "<div class='message'>$message</div>"; ?>
    <form method="POST">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="tel" name="phone" placeholder="Phone Number" required>
        <input type="password" name="password" placeholder="Password" required id="password">
        <input type="password" name="cpassword" placeholder="Confirm Password" required id="cpassword">
        <input type="checkbox" onclick="togglePassword()" class="show-pass"> Show Password
        <input type="submit" name="submit" value="Register">
    </form>
    <p><a href="index.php">Already registered? Go back to Homepage</a></p>
</div>

<script>
function togglePassword(){
    var p = document.getElementById("password");
    var c = document.getElementById("cpassword");
    if(p.type==="password"){ p.type="text"; c.type="text"; }
    else{ p.type="password"; c.type="password"; }
}
</script>
</body>
</html>

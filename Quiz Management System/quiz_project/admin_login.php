<?php
session_start();
require_once 'sql.php';
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM admin WHERE email='$email' AND password='$password'";
    $res = mysqli_query($conn, $sql);

    if(mysqli_num_rows($res) == 1){
        $row = mysqli_fetch_assoc($res);
        $_SESSION['admin_email'] = $row['email'];
        $_SESSION['user_id'] = $row['admin_id'];
        $_SESSION['username'] = $row['name'];
        $_SESSION['type'] = 'admin';
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Invalid Email or Password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - Quiz System</title>
<style>
body {
    font-family: Arial, sans-serif; margin:0; padding:0; background:#042A38; color:#fff;
}
.container {
    width:100vw; height:100vh; display:flex; justify-content:center; align-items:center;
}
.form-box {
    background:#fff; color:#042A38; padding:2rem; border-radius:10px; width:350px; text-align:center;
}
h2 { margin-bottom: 1rem; }
input[type=text], input[type=password] {
    width:90%; padding:0.5rem; margin:0.5rem 0; border-radius:5px; border:1px solid #ccc; display:block; margin-left:auto; margin-right:auto;
}
button {
    width:95%; padding:0.7rem; background:lightblue; border:none; border-radius:5px; font-weight:bold; cursor:pointer; margin-top:0.5rem;
}
a {
    color:#042A38; text-decoration:none; display:block; margin-top:0.5rem; font-size:0.9rem;
}
.show-pass {
    display:flex; justify-content:flex-start; align-items:center; font-size:0.9rem; margin-bottom:0.5rem;
}
.error {
    color:red; font-size:0.9rem; margin-bottom:0.5rem;
}
</style>
</head>
<body>
<div class="container">
    <div class="form-box">
        <h2>Admin Login</h2>
        <?php if(isset($error)){ echo "<p class='error'>$error</p>"; } ?>
        <form method="POST">
            <input type="text" name="email" placeholder="Email" required>
            <input type="password" id="password" name="password" placeholder="Password" required>
            <label class="show-pass"><input type="checkbox" onclick="togglePass()"> Show Password</label>
            <button type="submit" name="login">Login</button>
        </form>
        <a href="index.php">Back to Homepage</a>
    </div>
</div>
<script>
function togglePass(){ 
    var x = document.getElementById("password"); 
    x.type = x.type==="password"?"text":"password"; 
}
</script>
</body>
</html>

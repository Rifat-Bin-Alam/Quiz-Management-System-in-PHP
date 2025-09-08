<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Online Quiz System - Home</title>
<style>
    html, body {
        margin: 0;
        padding: 0;
        height: 100%;
        width: 100%;
        font-family: 'Roboto', sans-serif;
        background-color: #f0f0f0;
        color: #042A38;
    }
    .header {
        width: 100%;
        background-color: #042A38;
        color: #fff;
        padding: 1rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-sizing: border-box;
    }
    .header h1 {
        margin: 0;
        font-family: 'Libre Baskerville', serif;
    }
    .header a {
        color: #fff;
        text-decoration: none;
        font-weight: bold;
        padding: 0.5rem 1rem;
        background-color: #ff6600;
        border-radius: 5px;
    }
    .container {
        height: calc(100% - 70px);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        width: 100%;
        box-sizing: border-box;
    }
    .login-box {
        background-color: #fff;
        padding: 3rem 5rem;
        border-radius: 10px;
        box-shadow: 0px 0px 15px rgba(0,0,0,0.2);
        width: 30%;
        min-width: 300px;
    }
    .login-box h2 {
        margin-bottom: 2rem;
    }
    .login-box a {
        display: block;
        margin: 1rem 0;
        padding: 1rem 2rem;
        background-color: lightblue;
        color: #042A38;
        font-weight: bold;
        text-decoration: none;
        border-radius: 5px;
    }
    .register {
        margin-top: 2rem;
        font-size: 0.9rem;
    }
    .register a {
        color: #ff6600;
        text-decoration: none;
        font-weight: bold;
    }
    @media screen and (max-width: 768px) {
        .login-box {
            width: 80%;
            padding: 2rem;
        }
    }
</style>
</head>
<body>
    <div class="header">
        <h1>Quiz System</h1>
        <a href="admin_login.php">Admin Login</a>
    </div>

    <div class="container">
        <div class="login-box">
            <h2>Login</h2>
            <a href="student_login.php">Student Login</a>
            <a href="teacher_login.php">Teacher Login</a>

            <div class="register">
                Not registered yet? <br>
                <a href="signup_student.php">Student Registration</a> 
                <a href="signup_teacher.php">Teacher Registration</a>
            </div>
        </div>
    </div>
</body>
</html>

<?php
session_start();
require_once 'sql.php';
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

// Check admin login
if(!isset($_SESSION['admin_email'])){
    header("Location: admin_login.php");
    exit();
}

// Approve teacher
if(isset($_GET['id'])){
    $teacher_id = intval($_GET['id']); // sanitize input

    $sql = "UPDATE teacher SET status='approved' WHERE teacher_id=$teacher_id";
    if(mysqli_query($conn, $sql)){
        // Successfully approved, redirect back
        header("Location: admin_dashboard.php");
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
} else {
    echo "Invalid request!";
}
?>

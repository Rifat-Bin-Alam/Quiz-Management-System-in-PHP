<?php
session_start();
require_once 'sql.php';
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

// Check if admin is logged in
if(!isset($_SESSION['admin_email'])){
    header("Location: admin_login.php");
    exit();
}

// Logout
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: index.php");
    exit();
}

// Handle search queries
$teacher_search = isset($_GET['teacher_search']) ? mysqli_real_escape_string($conn,$_GET['teacher_search']) : '';
$student_search = isset($_GET['student_search']) ? mysqli_real_escape_string($conn,$_GET['student_search']) : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Quiz System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; margin:0; padding:0; background:#f2f2f2; }
        .header { background:#042A38; color:white; padding:1rem; display:flex; justify-content:space-between; align-items:center; }
        .container { width:95%; margin:2rem auto; background:white; padding:1rem; border-radius:8px; }
        h2 { text-align:center; color:#042A38; margin-bottom:0.5rem; }
        table { width:100%; border-collapse:collapse; margin-top:1rem; }
        th, td { border:1px solid #ddd; padding:0.5rem; text-align:center; }
        th { background:#042A38; color:white; }
        button { padding:0.4rem 0.8rem; background-color:lightblue; color:#042A38; border:none; border-radius:5px; cursor:pointer; margin:0.2rem; }
        button.decline { background-color:red; color:white; }
        input[type=text] { width:250px; padding:0.4rem; border-radius:5px; border:1px solid #ccc; margin-bottom:0.5rem; }
        form { margin-bottom:1rem; text-align:center; }
        a.logout { color:white; text-decoration:none; font-weight:bold; border:1px solid white; padding:0.3rem 0.5rem; border-radius:5px; }
    </style>
</head>
<body>

<div class="header">
    <h1>Admin Dashboard</h1>
    <a href="?logout=true" class="logout">Logout</a>
</div>

<!-- Pending Teachers Section -->
<div class="container">
    <h2>Pending Teachers</h2>
   
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Teacher Name</th>
                <th>Email</th>
                <th>Phone No</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM teacher WHERE status='pending'";
            if($teacher_search != ''){
                $sql .= " AND t_name LIKE '%$teacher_search%'";
            }
            $res = mysqli_query($conn,$sql);
            if($res && mysqli_num_rows($res) > 0){
                while($row = mysqli_fetch_assoc($res)){
                    echo "<tr>";
                    echo "<td>".$row['teacher_id']."</td>";
                    echo "<td>".$row['t_name']."</td>";
                    echo "<td>".$row['email']."</td>";
                    echo "<td>".$row['phone_no']."</td>";
                    echo "<td>".$row['status']."</td>";
                    echo "<td>
                            <a href='approve_teacher.php?id=".$row['teacher_id']."'><button>Approve</button></a>
                            <a href='delete_teacher.php?id=".$row['teacher_id']."'><button class='decline'>Delete</button></a>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No pending teachers found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<!-- All Teachers Section -->
<div class="container">
     <form method="GET">
        <input type="text" name="teacher_search" placeholder="Search teacher by name" value="<?php echo htmlspecialchars($teacher_search); ?>">
        <button type="submit">Search</button>
    </form>
    <h2>All Teachers</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Teacher Name</th>
                <th>Email</th>
                <th>Phone No</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM teacher";
            if($teacher_search != ''){
                $sql .= " WHERE t_name LIKE '%$teacher_search%'";
            }
            $res = mysqli_query($conn,$sql);
            if($res && mysqli_num_rows($res) > 0){
                while($row = mysqli_fetch_assoc($res)){
                    echo "<tr>";
                    echo "<td>".$row['teacher_id']."</td>";
                    echo "<td>".$row['t_name']."</td>";
                    echo "<td>".$row['email']."</td>";
                    echo "<td>".$row['phone_no']."</td>";
                    echo "<td>".$row['status']."</td>";
                    echo "<td>
                            <a href='delete_teacher.php?id=".$row['teacher_id']."'><button class='decline'>Delete</button></a>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No teachers found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Students Section -->
<div class="container">
    <h2>All Students</h2>
    <form method="GET">
        <input type="text" name="student_search" placeholder="Search student by name" value="<?php echo htmlspecialchars($student_search); ?>">
        <button type="submit">Search</button>
    </form>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Student Name</th>
                <th>Email</th>
                <th>Phone No</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM student";
            if($student_search != ''){
                $sql .= " WHERE s_name LIKE '%$student_search%'";
            }
            $res = mysqli_query($conn,$sql);
            if($res && mysqli_num_rows($res) > 0){
                while($row = mysqli_fetch_assoc($res)){
                    echo "<tr>";
                    echo "<td>".$row['student_id']."</td>";
                    echo "<td>".$row['s_name']."</td>";
                    echo "<td>".$row['email']."</td>";
                    echo "<td>".$row['phone_no']."</td>";
                    echo "<td>
                            <a href='delete_student.php?id=".$row['student_id']."'><button class='decline'>Delete</button></a>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5'>No students found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>

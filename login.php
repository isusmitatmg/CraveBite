<?php
session_start();
include "config/db.php";
$message = "";

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];
    $query = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn,$query);
    if(mysqli_num_rows($result) > 0){

        $user = mysqli_fetch_assoc($result);
        if(password_verify($password, $user['password'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            if($user['role'] == "admin"){
                header("Location: admin/dashboard.php");
            }
            else{
                header("Location: user/dashboard.php");
            }
            exit();
        }
        else{
            $message = "Incorrect password!";
        }
    }
    else{
        $message = "Account not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login | CraveBite</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="form-container">
<a href="index.php" class="back-home"> Back to Home</a>

<h2>Login</h2>
<p><?php echo $message; ?></p>
<form method="POST">

<label>Email</label>
<input type="email" name="email" required>

<label>Password</label>
<input type="password" name="password" required>

<button type="submit" name="login"> Login </button>
</form>

<p>Don't have an account? <a href="register.php">Register</a></p>
</div>

</body>
</html>
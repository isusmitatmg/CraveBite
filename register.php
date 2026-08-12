<?php
include "config/db.php";

$message = "";

if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = "user";

    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

    if (mysqli_num_rows($check) > 0) {
        $message = "Email already exists!";
    } else {
        $query = "INSERT INTO users (name, email, phone, password, role)
                  VALUES ('$name', '$email', '$phone', '$password', '$role')";

       if (mysqli_query($conn, $query)) {
    header("Location: login.php");
    exit();
} else {
    $message = "Registration failed!";
}
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register | CraveBite</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="form-container">

    <a href="index.php" class="back-home">← Back to Home</a>

    <h2>Create Account</h2>

    <p><?php echo htmlspecialchars($message); ?></p>

    <form method="POST">

        <label>Name</label>
        <input type="text" name="name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Phone Number</label>
        <input type="tel" name="phone" required placeholder="98XXXXXXXX">

        <label>Password</label>
        <input type="password" name="password" required minlength="6">

        <button type="submit" name="register">Register</button>

    </form>

    <p>Already have account? <a href="login.php">Login</a></p>

</div>

</body>
</html>
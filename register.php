<?php

include "config/db.php";

$message = "";

if (isset($_POST['register'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = "user";

    if (empty($name)) {

        $message = "Please enter your full name.";

    } elseif (!preg_match("/^[a-zA-Z]+(?:\s+[a-zA-Z]+)+$/", $name)) {

        $message = "Please enter your full name using letters only.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } elseif (!preg_match("/\A[a-zA-Z0-9._%+-]+@(gmail|hotmail|outlook)\.com\z/i", $email)) {

        $message = "Only valid Gmail, Hotmail, or Outlook email addresses are allowed.";

    } elseif (!preg_match("/^(98|97)[0-9]{8}$/", $phone)) {

        $message = "Please enter a valid 10-digit phone number.";

    } elseif (strlen($password) < 8) {

        $message = "Password must be at least 8 characters.";

    } elseif (!preg_match("/[A-Z]/", $password)) {

        $message = "Password must contain at least one uppercase letter.";

    } elseif (!preg_match("/[a-z]/", $password)) {

        $message = "Password must contain at least one lowercase letter.";

    } elseif (!preg_match("/[0-9]/", $password)) {

        $message = "Password must contain at least one number.";

    } elseif (!preg_match("/[\W_]/", $password)) {

        $message = "Password must contain at least one special character.";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";

    } else {

        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE email = ?"
        );

        mysqli_stmt_bind_param(
            $check,
            "s",
            $email
        );

        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $message = "Email already exists!";

        } else {

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $query = mysqli_prepare(
                $conn,
                "INSERT INTO users
                (name, email, phone, password, role)
                VALUES (?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $query,
                "sssss",
                $name,
                $email,
                $phone,
                $hashed_password,
                $role
            );

            if (mysqli_stmt_execute($query)) {

                header("Location: login.php");
                exit();

            } else {

                $message = "Registration failed. Please try again.";

            }

            mysqli_stmt_close($query);
        }

        mysqli_stmt_close($check);
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Register | CraveBite</title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>

<body>

<div class="form-container">

    <a
        href="index.php"
        class="back-home"
    >
        ← Back to Home
    </a>

    <h2>
        Create Account
    </h2>

    <?php if (!empty($message)): ?>

        <p>
            <?php echo htmlspecialchars($message); ?>
        </p>

    <?php endif; ?>

    <form method="POST">

        <label>
            Full Name
        </label>

        <input
            type="text"
            name="name"
            required
            pattern="[A-Za-z]+(\s+[A-Za-z]+)+"
            title="Please enter your full name, for example: Susmita Tamang."
            placeholder="Enter your full name"
        >

        <label>
            Email
        </label>

        <input
            type="email"
            name="email"
            required
            pattern="[A-Za-z0-9._%+-]+@(gmail|hotmail|outlook)\.com"
            title="Only valid Gmail, Hotmail, or Outlook email addresses are allowed."
            placeholder="example@gmail.com"
        >

        <label>
            Phone Number
        </label>

        <input
            type="tel"
            name="phone"
            required
            maxlength="10"
            pattern="(98|97)[0-9]{8}"
            title="Phone number must be 10 digits and start with 97 or 98."
            placeholder="98XXXXXXXX"
        >

        <label>
            Password
        </label>

        <input
            type="password"
            name="password"
            required
            minlength="8"
            pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).{8,}"
            title="Password must be at least 8 characters and contain an uppercase letter, lowercase letter, number, and special character."
            placeholder="At least 8 characters"
        >

        <label>
            Confirm Password
        </label>

        <input
            type="password"
            name="confirm_password"
            required
            minlength="8"
            placeholder="Re-enter your password"
        >

        <button
            type="submit"
            name="register"
        >
            Register
        </button>

    </form>

    <p>

        Already have an account?

        <a href="login.php">
            Login
        </a>

    </p>

</div>

</body>

</html>
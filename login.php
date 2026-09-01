<?php

session_start();

include "config/db.php";

$message = "";

/* LOGIN */

if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    /* FIND USER */

    $query = "
        SELECT id, name, email, password, role
        FROM users
        WHERE email = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {

        $message = "Database error: " . mysqli_error($conn);

    } else {

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) == 1) {

            mysqli_stmt_bind_result(
                $stmt,
                $user_id,
                $user_name,
                $user_email,
                $user_password,
                $user_role
            );

            mysqli_stmt_fetch($stmt);

            /* CHECK PASSWORD */

            if (password_verify($password, $user_password)) {

                /* STORE SESSIO */

                $_SESSION['user_id'] = $user_id;
                $_SESSION['name'] = $user_name;
                $_SESSION['role'] = $user_role;

                mysqli_stmt_close($stmt);


                /* =========================
                   MOVE GUEST CART
                   TO USER CART
                ========================= */

                if (
                    isset($_SESSION['guest_cart']) &&
                    !empty($_SESSION['guest_cart'])
                ) {

                    foreach (
                        $_SESSION['guest_cart']
                        as $food_id => $quantity
                    ) {

                        $food_id = (int)$food_id;
                        $quantity = (int)$quantity;

                        if ($food_id <= 0 || $quantity <= 0) {
                            continue;
                        }

                        if ($quantity > 20) {
                            $quantity = 20;
                        }

                        /* Check existing cart item */

                        $check_query = "
                            SELECT id, quantity
                            FROM cart
                            WHERE user_id = ?
                            AND food_id = ?
                            LIMIT 1
                        ";

                        $check_stmt = mysqli_prepare(
                            $conn,
                            $check_query
                        );

                        if (!$check_stmt) {
                            continue;
                        }

                        mysqli_stmt_bind_param(
                            $check_stmt,
                            "ii",
                            $user_id,
                            $food_id
                        );

                        mysqli_stmt_execute($check_stmt);

                        mysqli_stmt_store_result($check_stmt);

                        if (mysqli_stmt_num_rows($check_stmt) > 0) {

                            mysqli_stmt_bind_result(
                                $check_stmt,
                                $cart_id,
                                $old_quantity
                            );

                            mysqli_stmt_fetch($check_stmt);

                            $new_quantity =
                                $old_quantity + $quantity;

                            if ($new_quantity > 20) {
                                $new_quantity = 20;
                            }

                            mysqli_stmt_close($check_stmt);

                            $update_query = "
                                UPDATE cart
                                SET quantity = ?, added_at = NOW()
                                WHERE id = ?
                            ";

                            $update_stmt = mysqli_prepare(
                                $conn,
                                $update_query
                            );

                            if ($update_stmt) {

                                mysqli_stmt_bind_param(
                                    $update_stmt,
                                    "ii",
                                    $new_quantity,
                                    $cart_id
                                );

                                mysqli_stmt_execute($update_stmt);

                                mysqli_stmt_close($update_stmt);
                            }

                        } else {

                            mysqli_stmt_close($check_stmt);

                            $insert_query = "
                                INSERT INTO cart
                                (
                                    user_id,
                                    food_id,
                                    quantity,
                                    added_at
                                )
                                VALUES (?, ?, ?, NOW())
                            ";

                            $insert_stmt = mysqli_prepare(
                                $conn,
                                $insert_query
                            );

                            if ($insert_stmt) {

                                mysqli_stmt_bind_param(
                                    $insert_stmt,
                                    "iii",
                                    $user_id,
                                    $food_id,
                                    $quantity
                                );

                                mysqli_stmt_execute($insert_stmt);

                                mysqli_stmt_close($insert_stmt);
                            }
                        }
                    }

                    /* Clear guest cart */

                    unset($_SESSION['guest_cart']);
                }


                /*  ADMIN REDIRECT */

                if ($user_role === "admin") {

                    header(
                        "Location: admin/dashboard.php"
                    );

                    exit();
                }


                /* CHECKOUT REDIRECT */

                if (
                    isset($_SESSION['redirect_after_register']) &&
                    !empty($_SESSION['redirect_after_register'])
                ) {

                    $redirect =
                        $_SESSION['redirect_after_register'];

                    unset(
                        $_SESSION['redirect_after_register']
                    );

                    header("Location: " . $redirect);

                    exit();
                }


                /* =========================
                   NORMAL USER REDIRECT
                ========================= */

                header("Location: menu.php");

                exit();

            } else {

                $message = "Incorrect password!";
            }

        } else {

            $message = "Account not found!";
        }

        mysqli_stmt_close($stmt);
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

    <title>Login | CraveBite</title>

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
        Back to Home
    </a>

    <h2>
        Login
    </h2>

    <?php if (!empty($message)): ?>

        <p>
            <?php
            echo htmlspecialchars($message);
            ?>
        </p>

    <?php endif; ?>

    <form method="POST">

        <label>
            Email
        </label>

        <input
            type="email"
            name="email"
            required
            placeholder="Enter your email"
        >

        <label>
            Password
        </label>

        <input
            type="password"
            name="password"
            required
            placeholder="Enter your password"
        >

        <button
            type="submit"
            name="login"
        >
            Login
        </button>

    </form>

    <p>

        Don't have an account?

        <a href="register.php">
            Register
        </a>

    </p>

</div>

</body>

</html>

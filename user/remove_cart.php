<?php

session_start();
include '../config/db.php';

if (!isset($_GET['food_id'])) {
    header("Location: ../cart.php");
    exit();
}

$food_id = (int) $_GET['food_id'];

if ($food_id <= 0) {
    header("Location: ../cart.php");
    exit();
}

/* =====================================
   LOGGED-IN USER
===================================== */

if (isset($_SESSION['user_id'])) {

    $user_id = (int) $_SESSION['user_id'];

    $query = "DELETE FROM cart
              WHERE user_id = ?
              AND food_id = ?";

    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        die("Remove cart query failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $user_id,
        $food_id
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
}

/* =====================================
   GUEST USER
===================================== */

else {

    if (isset($_SESSION['guest_cart'][$food_id])) {
        unset($_SESSION['guest_cart'][$food_id]);
    }
}

/* Go back to main cart */

header("Location: ../cart.php");
exit();

?>
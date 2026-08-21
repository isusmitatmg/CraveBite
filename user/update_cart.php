<?php

session_start();
include '../config/db.php';


/* Check food ID and action */

if (!isset($_GET['food_id']) || !isset($_GET['action'])) {

    header("Location: ../cart.php");
    exit();

}


$food_id = (int) $_GET['food_id'];
$action = $_GET['action'];


if ($food_id <= 0) {

    header("Location: ../cart.php");
    exit();

}


/* =====================================
   LOGGED-IN USER
===================================== */

if (isset($_SESSION['user_id'])) {

    $user_id = (int) $_SESSION['user_id'];


    $query = "SELECT quantity
              FROM cart
              WHERE user_id = ?
              AND food_id = ?";


    $stmt = mysqli_prepare($conn, $query);


    if (!$stmt) {

        die("Cart query failed: " . mysqli_error($conn));

    }


    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $user_id,
        $food_id
    );


    mysqli_stmt_execute($stmt);


    $result = mysqli_stmt_get_result($stmt);


    if ($row = mysqli_fetch_assoc($result)) {

        $quantity = (int) $row['quantity'];


        /* Increase */

        if ($action == "increase") {

            if ($quantity < 20) {
                $quantity++;
            }

        }


        /* Decrease */

        elseif ($action == "decrease") {

            if ($quantity > 1) {
                $quantity--;
            }

        }


        /* Update database */

        $update_query = "UPDATE cart
                         SET quantity = ?
                         WHERE user_id = ?
                         AND food_id = ?";


        $update_stmt = mysqli_prepare(
            $conn,
            $update_query
        );


        if (!$update_stmt) {

            die("Update query failed: " . mysqli_error($conn));

        }


        mysqli_stmt_bind_param(
            $update_stmt,
            "iii",
            $quantity,
            $user_id,
            $food_id
        );


        mysqli_stmt_execute($update_stmt);


        mysqli_stmt_close($update_stmt);

    }


    mysqli_stmt_close($stmt);

}


/* =====================================
   GUEST USER
===================================== */

else {

    if (isset($_SESSION['guest_cart'][$food_id])) {

        $quantity = (int) $_SESSION['guest_cart'][$food_id];


        /* Increase */

        if ($action == "increase") {

            if ($quantity < 20) {
                $quantity++;
            }

        }


        /* Decrease */

        elseif ($action == "decrease") {

            if ($quantity > 1) {
                $quantity--;
            }

        }


        $_SESSION['guest_cart'][$food_id] = $quantity;

    }

}


/* Go back to main cart */

header("Location: ../cart.php");
exit();

?>
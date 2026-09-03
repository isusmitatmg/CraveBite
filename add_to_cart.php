<?php

session_start();
include "config/db.php";

/* Remove cart items older than 2 days */
mysqli_query($conn, "DELETE FROM cart WHERE added_at < NOW() - INTERVAL 2 DAY");

/* Only allow POST request */
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: menu.php");
    exit();
}

$food_id = isset($_POST['food_id']) ? (int)$_POST['food_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($food_id <= 0) {
    header("Location: menu.php");
    exit();
}

$quantity = max(1, min(20, $quantity));

/* Check if food exists */
$food_query = "SELECT id FROM food WHERE id = ?";
$stmt = mysqli_prepare($conn, $food_query);

if (!$stmt) {
    header("Location: menu.php");
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $food_id);
mysqli_stmt_execute($stmt);
$food_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($food_result) == 0) {
    mysqli_stmt_close($stmt);
    header("Location: menu.php");
    exit();
}

mysqli_stmt_close($stmt);

/* LOGGED-IN USER*/

if (isset($_SESSION['user_id'])) {

    $user_id = (int) $_SESSION['user_id'];

    $check_query = "SELECT id, quantity
                    FROM cart
                    WHERE user_id = ?
                    AND food_id = ?";

    $stmt = mysqli_prepare($conn, $check_query);

    mysqli_stmt_bind_param($stmt, "ii", $user_id, $food_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {

        $cart_item = mysqli_fetch_assoc($result);

        $new_quantity = min($cart_item['quantity'] + $quantity, 20);

        /* Reset the 2-day timer */

        $update_query = "UPDATE cart
                         SET quantity = ?, added_at = NOW()
                         WHERE user_id = ?
                         AND food_id = ?";

        $update_stmt = mysqli_prepare($conn, $update_query);

        mysqli_stmt_bind_param(
            $update_stmt,
            "iii",
            $new_quantity,
            $user_id,
            $food_id
        );

        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);

    } else {

        /* added_at uses CURRENT_TIMESTAMP automatically */

        $insert_query = "INSERT INTO cart
                         (user_id, food_id, quantity)
                         VALUES (?, ?, ?)";

        $insert_stmt = mysqli_prepare($conn, $insert_query);

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

    mysqli_stmt_close($stmt);

}

/*  GUEST USER\ */

else {

    if (!isset($_SESSION['guest_cart'])) {
        $_SESSION['guest_cart'] = [];
    }

    if (isset($_SESSION['guest_cart'][$food_id])) {

        $_SESSION['guest_cart'][$food_id] = min(
            $_SESSION['guest_cart'][$food_id] + $quantity,
            20
        );

    } else {

        $_SESSION['guest_cart'][$food_id] = $quantity;
    }

}

/* AJAX REQUEST*/

if (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === "xmlhttprequest"
) {
    http_response_code(204);
    exit();
}

/* Normal request */

header("Location: menu.php");
exit();

?>
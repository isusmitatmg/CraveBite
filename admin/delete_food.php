<?php
session_start();

include "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {

    $id = $_GET['id'];

    mysqli_query(
        $conn,
        "DELETE FROM order_items WHERE food_id='$id'"
    );

    $delete = mysqli_query(
        $conn,
        "DELETE FROM food WHERE id='$id'"
    );

    if (!$delete) {
        die("Delete failed: " . mysqli_error($conn));
    }
}

header("Location: manage_food.php");

exit();

?>
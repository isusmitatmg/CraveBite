<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: order.php");
    exit();
}

$order_id = $_GET['id'];

$order_stmt = mysqli_prepare(
    $conn,
    "SELECT orders.*, users.name
     FROM orders
     JOIN users
     ON orders.user_id = users.id
     WHERE orders.id = ?"
);

mysqli_stmt_bind_param($order_stmt, "i", $order_id);
mysqli_stmt_execute($order_stmt);

$order_result = mysqli_stmt_get_result($order_stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    header("Location: order.php");
    exit();
}

$item_stmt = mysqli_prepare(
    $conn,
    "SELECT order_items.*, food.name, food.image
     FROM order_items
     JOIN food
     ON order_items.food_id = food.id
     WHERE order_items.order_id = ?"
);

mysqli_stmt_bind_param($item_stmt, "i", $order_id);
mysqli_stmt_execute($item_stmt);

$item_result = mysqli_stmt_get_result($item_stmt);

?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Order Details | CraveBite</title>

    <link
        rel="stylesheet"
        href="../css/admin-order-details.css"
    >

</head>

<body>

<div class="navbar">

    <a href="dashboard.php?page=orders">
        &larr; Manage Orders
    </a>

</div>

<div class="container">

    <h2>
        Order Details
    </h2>

    <div class="order-info">

        <p>
            <strong>Customer:</strong>

            <?php
            echo htmlspecialchars($order['name']);
            ?>
        </p>

        <p>
            <strong>Order ID:</strong>

            <?php
            echo $order['id'];
            ?>
        </p>

        <p>
            <strong>Date & Time:</strong>

            <?php
            echo date(
                "d M Y, h:i A",
                strtotime($order['order_date'])
            );
            ?>
        </p>

        <p>
            <strong>Total:</strong>

            Rs.
            <?php
            echo number_format($order['total_price'], 2);
            ?>
        </p>

        <p>
            <strong>Status:</strong>

            <?php
            echo htmlspecialchars($order['status']);
            ?>
        </p>

    </div>

    <table>

        <tr>

            <th>Image</th>

            <th>Food</th>

            <th>Quantity</th>

            <th>Price</th>

        </tr>

        <?php while ($item = mysqli_fetch_assoc($item_result)): ?>

        <tr>

            <td>

                <img
                    src="../uploads/<?php echo htmlspecialchars($item['image']); ?>"
                    alt="Food Image"
                >

            </td>

            <td>

                <?php
                echo htmlspecialchars($item['name']);
                ?>

            </td>

            <td>

                <?php
                echo $item['quantity'];
                ?>

            </td>

            <td>

                Rs.
                <?php
                echo number_format($item['price'], 2);
                ?>

            </td>

        </tr>

        <?php endwhile; ?>

    </table>

</div>

</body>

</html>
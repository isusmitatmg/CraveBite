<?php

session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order Details | CraveBite</title>

    <style>

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fff8f0;
        }

        .navbar {
            background: #f57c00;
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .container {
            width: 90%;
            max-width: 900px;
            margin: 40px auto;
        }

        .container h2 {
            color: #f57c00;
            margin-bottom: 25px;
        }

        .order-info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .order-info p {
            margin: 10px 0;
        }

        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        th {
            background: #f57c00;
            color: white;
            padding: 12px;
            text-align: left;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        td img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }

    </style>

</head>

<body>

<div class="navbar">

    <a href="order.php">
        &larr; Manage Orders
    </a>

    <a href="../logout.php">
        Logout
    </a>

</div>

<div class="container">

    <h2>
        Order Details
    </h2>

    <div class="order-info">

        <p>
            <strong>Customer:</strong>
            <?php echo htmlspecialchars($order['name']); ?>
        </p>

        <p>
    <strong>Order ID:</strong>
    <?php echo $order['id']; ?>
</p>

<p>
    <strong>Date & Time:</strong>
    <?php echo date("d M Y, h:i A", strtotime($order['order_date'])); ?>
</p>

<p>
    <strong>Total:</strong>
    Rs. <?php echo number_format($order['total_price'], 2); ?>
</p>


        <p>
            <strong>Status:</strong>
            <?php echo htmlspecialchars($order['status']); ?>
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
                <?php echo htmlspecialchars($item['name']); ?>
            </td>

            <td>
                <?php echo $item['quantity']; ?>
            </td>

            <td>
                Rs. <?php echo number_format($item['price'], 2); ?>
            </td>

        </tr>

        <?php endwhile; ?>

    </table>

</div>

</body>
</html>
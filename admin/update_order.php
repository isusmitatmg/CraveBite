<?php

session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
    header("Location: order.php");
    exit();
}

$order_id = $_POST['order_id'];
$status = $_POST['status'];

$allowed_statuses = [
    "Pending",
    "Confirmed",
    "Preparing",
    "Ready",
    "Completed",
    "Cancelled"
];

if (!in_array($status, $allowed_statuses)) {
    die("Invalid order status.");
}

$stmt = mysqli_prepare(
    $conn,
    "UPDATE orders SET status = ? WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "si", $status, $order_id);

if (mysqli_stmt_execute($stmt)) {
    $message = "Order Updated!";
    $success = true;
} else {
    $message = "Update failed: " . mysqli_error($conn);
    $success = false;
}

mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order Updated | CraveBite</title>

    <meta http-equiv="refresh" content="2;url=order.php">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fff8f0;
            text-align: center;
            padding-top: 100px;
            margin: 0;
        }

        .box {
            background: white;
            width: 450px;
            max-width: 90%;
            margin: auto;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #f57c00;
        }

        .error {
            color: #d9534f;
        }

        a {
            color: #f57c00;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="box">

    <?php if ($success): ?>

        <h2>✅ Order Updated!</h2>

        <p>
            Order #<?php echo htmlspecialchars($order_id); ?>
            is now
            <strong><?php echo htmlspecialchars($status); ?></strong>.
        </p>

        <p>
            Returning to orders...
        </p>

        <p>
            <a href="order.php">
                &larr; Back to Orders
            </a>
        </p>

    <?php else: ?>

        <h2 class="error">
            ❌ Update Failed
        </h2>

        <p>
            <?php echo htmlspecialchars($message); ?>
        </p>

        <p>
            <a href="order.php">
                &larr; Back to Orders
            </a>
        </p>

    <?php endif; ?>

</div>

</body>
</html>
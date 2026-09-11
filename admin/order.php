<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config/db.php';

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Update order status
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];

    $allowed_statuses = [
        'Pending',
        'Confirmed',
        'Preparing',
        'Ready',
        'Completed',
        'Cancelled'
    ];

    if (in_array($status, $allowed_statuses)) {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE orders SET status=? WHERE id=?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $status,
            $order_id
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Get orders with customer and food items
$query = "SELECT
            o.id,
            o.total_price,
            o.status,
            o.order_date,
            u.name AS customer_name,
            GROUP_CONCAT(
                CONCAT(f.name, ' (x', oi.quantity, ')')
                ORDER BY f.name
                SEPARATOR ', '
            ) AS food_items
          FROM orders o
          JOIN users u ON o.user_id = u.id
          LEFT JOIN order_items oi ON o.id = oi.order_id
          LEFT JOIN food f ON oi.food_id = f.id
          GROUP BY
            o.id,
            o.total_price,
            o.status,
            o.order_date,
            u.name
          ORDER BY o.order_date DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("SQL Error: " . mysqli_error($conn));
}

$statuses = [
    'Pending',
    'Confirmed',
    'Preparing',
    'Ready',
    'Completed',
    'Cancelled'
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Orders - CraveBite</title>

    <link rel="stylesheet" href="../css/admin-order.css">
</head>

<body>

    <div class="container">

        <h2>
            Customer Orders
        </h2>

        <table>

            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Food Items</th>
                <th>Total</th>
                <th>Status</th>
                <th>Details</th>
                <th>Update Status</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($result)): ?>

                <tr>

                    <td>
                        <?php echo $row['id']; ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $row['customer_name']
                        );
                        ?>
                    </td>

                    <td class="food-items">
                        <?php
                        echo htmlspecialchars(
                            $row['food_items'] ?? 'No Items'
                        );
                        ?>
                    </td>

                    <td>
                        Rs
                        <?php
                        echo number_format(
                            $row['total_price'],
                            2
                        );
                        ?>
                    </td>

                    <td>
                        <span
                            class="status status-<?php echo strtolower($row['status']); ?>"
                        >
                            <?php echo $row['status']; ?>
                        </span>
                    </td>

                    <td>
                        <a
                            href="order_details.php?id=<?php echo $row['id']; ?>"
                        >
                            View
                        </a>
                    </td>

                    <td>

                        <form method="POST">

                            <input
                                type="hidden"
                                name="order_id"
                                value="<?php echo $row['id']; ?>"
                            >

                            <select name="status">

                                <?php foreach ($statuses as $s): ?>

                                    <option
                                        value="<?php echo $s; ?>"
                                        <?php echo ($s == $row['status']) ? 'selected' : ''; ?>
                                    >
                                        <?php echo $s; ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <button
                                type="submit"
                                name="update_status"
                            >
                                Update
                            </button>

                        </form>

                    </td>

                </tr>

            <?php endwhile; ?>

        </table>

    </div>

</body>

</html>
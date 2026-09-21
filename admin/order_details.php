<?php

if (!isset($_GET['id'])) {
    echo "<p>Order not found.</p>";
    return;
}

$order_id = (int) $_GET['id'];

$order_stmt = mysqli_prepare(
    $conn,
    "SELECT orders.*, users.name
     FROM orders
     JOIN users ON orders.user_id = users.id
     WHERE orders.id = ?"
);

if (!$order_stmt) {
    echo "<p>Unable to load order.</p>";
    return;
}

mysqli_stmt_bind_param($order_stmt, "i", $order_id);
mysqli_stmt_execute($order_stmt);

$order_result = mysqli_stmt_get_result($order_stmt);
$order = mysqli_fetch_assoc($order_result);

mysqli_stmt_close($order_stmt);

if (!$order) {
    echo "<p>Order not found.</p>";
    return;
}

$item_stmt = mysqli_prepare(
    $conn,
    "SELECT order_items.*, food.name, food.image
     FROM order_items
     JOIN food ON order_items.food_id = food.id
     WHERE order_items.order_id = ?"
);

if (!$item_stmt) {
    echo "<p>Unable to load order items.</p>";
    return;
}

mysqli_stmt_bind_param($item_stmt, "i", $order_id);
mysqli_stmt_execute($item_stmt);

$item_result = mysqli_stmt_get_result($item_stmt);
?>

<div class="order-details-page">

    <div class="page-header">

        <div>
            <h2>Order Details</h2>
            <p>View complete information about this order.</p>
        </div>

        <a href="dashboard.php?page=orders" class="back-btn">
           ← Back to Orders
       </a>

    </div>
    <br>

    <div class="order-info">

        <div class="info-row">
            <strong>Customer:</strong>
            <span>
                <?php echo htmlspecialchars($order['name']); ?>
            </span>
        </div>

        <div class="info-row">
            <strong>Order ID:</strong>
            <span>
                #<?php echo $order['id']; ?>
            </span>
        </div>

        <div class="info-row">
            <strong>Token Number:</strong>
            <span>
                #<?php echo htmlspecialchars($order['token']); ?>
            </span>
        </div>

        <div class="info-row">
            <strong>Date & Time:</strong>
            <span>
                <?php
                echo date(
                    "d M Y, h:i A",
                    strtotime($order['order_date'])
                );
                ?>
            </span>
        </div>

        <div class="info-row">
            <strong>Total:</strong>
            <span>
                Rs.
                <?php echo number_format($order['total_price'], 2); ?>
            </span>
        </div>

        <div class="info-row">
            <strong>Status:</strong>
            <span>
                <?php echo htmlspecialchars($order['status']); ?>
            </span>
        </div>

    </div>

    <div class="order-items">

        <h3>Ordered Food</h3>

        <table>

            <thead>
                <tr>
                    <th>Image</th>
                    <th>Food</th>
                    <th>Quantity</th>
                    <th>Price</th>
                </tr>
            </thead>

            <tbody>

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
                            Rs.
                            <?php
                            echo number_format(
                                $item['price'],
                                2
                            );
                            ?>
                        </td>

                    </tr>

                <?php endwhile; ?>

            </tbody>

        </table>

    </div>

</div>

<?php
mysqli_stmt_close($item_stmt);
?>
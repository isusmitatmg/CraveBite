<?php
session_start();
include '../config/db.php';

// User only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "user") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* =========================
   DELETE ORDER
========================= */
if (isset($_GET['delete'])) {

    $order_id = (int) $_GET['delete'];

    // Check ownership and status
    $check = mysqli_prepare($conn,
        "SELECT status FROM orders WHERE id = ? AND user_id = ?"
    );
    mysqli_stmt_bind_param($check, "ii", $order_id, $user_id);
    mysqli_stmt_execute($check);
    $check_result = mysqli_stmt_get_result($check);

    if ($row = mysqli_fetch_assoc($check_result)) {

        if (in_array($row['status'], ['Pending', 'Cancelled'])) {

            // Delete order items first
            $delete_items = mysqli_prepare($conn,
                "DELETE FROM order_items WHERE order_id = ?"
            );
            mysqli_stmt_bind_param($delete_items, "i", $order_id);
            mysqli_stmt_execute($delete_items);

            // Delete order
            $delete_order = mysqli_prepare($conn,
                "DELETE FROM orders WHERE id = ? AND user_id = ?"
            );
            mysqli_stmt_bind_param($delete_order, "ii", $order_id, $user_id);
            mysqli_stmt_execute($delete_order);
        }
    }

    header("Location: my_order.php");
    exit();
}

// Get user's orders
$sql = "SELECT id, total_price, status, order_date
        FROM orders
        WHERE user_id = ?
        ORDER BY order_date DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Order query failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Orders - CraveBite</title>

<link rel="stylesheet" href="../css/style.css">

<style>

body{
    background:#fff8f0;
    font-family:Arial, Helvetica, sans-serif;
}

.orders-container{
    width:95%;
    max-width:900px;
    margin:40px auto;
}

.orders-container h1{
    color:#f57c00;
    margin-bottom:8px;
}

.subtitle{
    color:#666;
    margin-bottom:18px;
}

/* Back Button */

.back-btn{
    display:inline-block;
    margin-bottom:25px;
    padding:11px 18px;
    background:#f57c00;
    color:white;
    text-decoration:none;
    border-radius:8px;
    font-weight:bold;
    transition:.2s;
}

.back-btn:hover{
    background:#e66d00;
}

.order-card{
    background:white;
    border-radius:15px;
    padding:22px;
    margin-bottom:25px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
}

.order-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    margin-bottom:18px;
}

.order-date{
    color:#555;
    font-size:14px;
}

.status{
    padding:7px 14px;
    border-radius:20px;
    font-size:13px;
    font-weight:bold;
}

.pending{background:#fff3cd;color:#856404;}
.confirmed{background:#d1ecf1;color:#0c5460;}
.preparing{background:#ffe0b2;color:#e65100;}
.ready{background:#d4edda;color:#155724;}
.completed{background:#c3e6cb;color:#155724;}
.cancelled{background:#f8d7da;color:#721c24;}

.items{
    margin:18px 0;
}

.item{
    display:flex;
    align-items:center;
    gap:15px;
    padding:12px 0;
    border-bottom:1px solid #eee;
}

.item:last-child{
    border-bottom:none;
}

.item img{
    width:60px;
    height:60px;
    object-fit:cover;
    border-radius:8px;
}

.item-name{
    font-weight:bold;
}

.item-info{
    color:#666;
    font-size:14px;
}

.order-bottom{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:18px;
    padding-top:15px;
    border-top:1px solid #eee;
}

.total{
    font-size:20px;
    font-weight:bold;
    color:#f57c00;
}

.actions{
    display:flex;
    align-items:center;
    gap:12px;
}

.delete-btn{
    background:#dc3545;
    color:white;
    text-decoration:none;
    padding:8px 14px;
    border-radius:8px;
    font-size:13px;
    font-weight:bold;
    transition:.2s;
}

.delete-btn:hover{
    background:#c82333;
}

.empty-orders{
    text-align:center;
    background:white;
    padding:60px 20px;
    border-radius:15px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
}

.browse-btn{
    display:inline-block;
    padding:12px 22px;
    background:#f57c00;
    color:white;
    text-decoration:none;
    border-radius:8px;
    font-weight:bold;
}

.browse-btn:hover{
    background:#e66d00;
}

@media(max-width:600px){

    .order-top,
    .order-bottom{
        flex-direction:column;
        align-items:flex-start;
        gap:10px;
    }

    .actions{
        flex-wrap:wrap;
    }

}
</style>

</head>

<body>

<div class="orders-container">

<h1>My Orders</h1>

<p class="subtitle">
Track your CraveBite orders and see exactly what you've ordered.
</p>

<a href="../index.php" class="back-btn">← Back to Home</a>

<?php if(mysqli_num_rows($result)>0): ?>

<?php while($order=mysqli_fetch_assoc($result)): ?>

<div class="order-card">

<div class="order-top">

<div>

<div class="order-date">
<?php echo date("d M Y, h:i A", strtotime($order['order_date'])); ?>
</div>

</div>

<span class="status <?php echo strtolower($order['status']); ?>">
<?php echo htmlspecialchars($order['status']); ?>
</span>

</div>

<div class="items">

<?php

$item_sql="SELECT food.name, food.image, order_items.quantity, order_items.price
           FROM order_items
           JOIN food ON order_items.food_id=food.id
           WHERE order_items.order_id=?";

$item_stmt=mysqli_prepare($conn,$item_sql);
mysqli_stmt_bind_param($item_stmt,"i",$order['id']);
mysqli_stmt_execute($item_stmt);
$item_result=mysqli_stmt_get_result($item_stmt);

while($item=mysqli_fetch_assoc($item_result)):
?>

<div class="item">

<img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="Food Image">

<div>

<div class="item-name">
<?php echo htmlspecialchars($item['name']); ?>
</div>

<div class="item-info">
Qty: <?php echo $item['quantity']; ?>
•
Rs. <?php echo number_format($item['price'],2); ?>
</div>

</div>

</div>

<?php endwhile; ?>

</div>

<div class="order-bottom">

<div class="actions">

<div class="total">
Rs. <?php echo number_format($order['total_price'],2); ?>
</div>

<?php if(in_array($order['status'], ['Pending','Cancelled'])): ?>

<a href="my_order.php?delete=<?php echo $order['id']; ?>"
   class="delete-btn"
   onclick="return confirm('Are you sure you want to delete this order?');">
Delete
</a>

<?php endif; ?>

</div>

</div>

</div>

<?php endwhile; ?>

<?php else: ?>

<div class="empty-orders">

<h2>No Orders Yet</h2>

<p>You haven't placed any orders yet.</p>

<a href="../menu.php" class="browse-btn">
Browse Food
</a>

</div>

<?php endif; ?>

</div>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>
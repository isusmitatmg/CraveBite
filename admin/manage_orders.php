<?php
session_start();
include '../config/db.php';

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

// Update order status
if (isset($_POST['update_status'])) {

    $order_id = $_POST['order_id'];
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
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

// Get all orders
$query = "SELECT orders.*, users.name
          FROM orders
          JOIN users
          ON orders.user_id = users.id
          ORDER BY orders.order_date DESC";

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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Orders - CraveBite</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;
}

body{
    background:#f5f5f5;
    color:#333;
}

.navbar{
    background:#ff6b00;
    padding:15px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.navbar a{
    color:white;
    text-decoration:none;
    font-weight:bold;
    padding:8px 15px;
    border-radius:6px;
    transition:.3s;
}

.navbar a:hover{
    background:rgba(255,255,255,.2);
}

.container{
    width:95%;
    max-width:1200px;
    margin:30px auto;
}

h2{
    color:#ff6b00;
    margin-bottom:20px;
}

table{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 4px 12px rgba(0,0,0,.08);
}

th{
    background:#ff6b00;
    color:white;
    padding:14px;
    text-align:left;
}

td{
    padding:14px;
    border-bottom:1px solid #eee;
    vertical-align:middle;
}

tr:hover{
    background:#fff8f3;
}

.status{
    display:inline-block;
    padding:6px 12px;
    border-radius:20px;
    color:white;
    font-size:13px;
    font-weight:bold;
}

.status-pending{background:#f39c12;}
.status-confirmed{background:#3498db;}
.status-preparing{background:#9b59b6;}
.status-ready{background:#16a085;}
.status-completed{background:#27ae60;}
.status-cancelled{background:#e74c3c;}

form{
    display:flex;
    gap:8px;
    align-items:center;
}

select{
    padding:8px 10px;
    border:1px solid #ccc;
    border-radius:6px;
    background:white;
}

button{
    background:#ff6b00;
    color:white;
    border:none;
    padding:8px 14px;
    border-radius:6px;
    cursor:pointer;
    font-weight:bold;
}

button:hover{
    background:#e65c00;
}

td a{
    color:#ff6b00;
    text-decoration:none;
    font-weight:bold;
}

td a:hover{
    text-decoration:underline;
}

@media(max-width:900px){
    table{
        display:block;
        overflow-x:auto;
        white-space:nowrap;
    }

    form{
        flex-direction:column;
        align-items:flex-start;
    }

    select,button{
        width:100%;
    }
}
</style>

</head>
<body>

<div class="navbar">
    <a href="dashboard.php">&larr; Dashboard</a>
    <a href="../logout.php">Logout</a>
</div>

<div class="container">

<h2>Customer Orders</h2>

<table>
<tr>
    <th>Order ID</th>
    <th>Customer</th>
    <th>Total</th>
    <th>Status</th>
    <th>Details</th>
    <th>Update Status</th>
</tr>

<?php while($row=mysqli_fetch_assoc($result)): ?>

<tr>

<td><?php echo $row['id']; ?></td>

<td><?php echo htmlspecialchars($row['name']); ?></td>

<td>Rs <?php echo number_format($row['total_price'],2); ?></td>

<td>
<span class="status status-<?php echo strtolower($row['status']); ?>">
<?php echo $row['status']; ?>
</span>
</td>

<td>
<a href="order_details.php?id=<?php echo $row['id']; ?>">View</a>
</td>

<td>
<form method="POST">

<input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">

<select name="status">

<?php foreach($statuses as $s): ?>

<option value="<?php echo $s; ?>" <?php echo $s==$row['status']?'selected':''; ?>>
<?php echo $s; ?>
</option>

<?php endforeach; ?>

</select>

<button type="submit" name="update_status">Update</button>

</form>
</td>

</tr>

<?php endwhile; ?>

</table>

</div>

</body>
</html>
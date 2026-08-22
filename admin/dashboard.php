
<?php

session_start();

include "../config/db.php";

/* =========================
   ADMIN ACCESS
========================= */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}
/* =========================
   UPDATE ORDER STATUS
========================= */

if (isset($_POST['update_status'])) {

    $order_id = intval($_POST['order_id']);
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
            "UPDATE orders SET status = ? WHERE id = ?"
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

    header("Location: dashboard.php");
    exit();
}

/* =========================
   DASHBOARD STATISTICS
========================= */

/* Total Customers */
$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'user'"
);

$customer_data = mysqli_fetch_assoc($result);
$total_customers = $customer_data['total'] ?? 0;


/* Total Food */
$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM food"
);

$food_data = mysqli_fetch_assoc($result);
$total_food = $food_data['total'] ?? 0;


/* Total Orders */
$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders"
);

$order_data = mysqli_fetch_assoc($result);
$total_orders = $order_data['total'] ?? 0;


/* Pending Orders */
$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE status = 'Pending'"
);

$pending_data = mysqli_fetch_assoc($result);
$pending_orders = $pending_data['total'] ?? 0;


/* Total Revenue */
$result = mysqli_query(
    $conn,
    "SELECT SUM(total_price) AS revenue
     FROM orders
     WHERE status != 'Cancelled'"
);

$revenue_data = mysqli_fetch_assoc($result);
$total_revenue = $revenue_data['revenue'] ?? 0;


/* Recent Orders */
$recent_orders = mysqli_query(
    $conn,
    "SELECT
        o.id,
        o.total_price,
        o.status,
        o.order_date,
        o.phone_number,
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
     GROUP BY o.id, o.total_price, o.status, o.order_date, o.phone_number, u.name
     ORDER BY o.order_date DESC
     LIMIT 5"
);

if (!$recent_orders) {
    die("SQL Error: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard - CraveBite</title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f7f7f7;
            color: #333;
        }


        /* =========================
           DASHBOARD
        ========================= */

        .dashboard {
            display: flex;
            min-height: 100vh;
        }


        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;

            width: 245px;
            height: 100vh;

            background: #ffffff;

            border-right: 1px solid #eeeeee;

            display: flex;
            flex-direction: column;

            z-index: 100;
        }


        /* =========================
           LOGO
        ========================= */

        .logo {
            height: 85px;

            display: flex;
            align-items: center;

            padding: 0 28px;

            border-bottom: 1px solid #eeeeee;
        }


        .logo h2 {
            font-size: 25px;
            color: #ff6b00;
            font-weight: 700;
        }


        .logo span {
            color: #222;
        }


        /* =========================
           ADMIN
        ========================= */

        .admin-name {
            padding: 18px 28px;

            border-bottom: 1px solid #eeeeee;

            color: #333;

            font-size: 14px;

            font-weight: 600;
        }


        /* =========================
           NAVIGATION
        ========================= */

        .navigation {
            padding: 25px 15px;
        }


        .navigation ul {
            list-style: none;
        }


        .navigation li {
            margin-bottom: 7px;
        }


        .navigation a {
            display: block;

            padding: 13px 16px;

            text-decoration: none;

            color: #555;

            font-size: 14px;

            font-weight: 500;

            border-radius: 9px;

            transition: all 0.25s ease;
        }


        .navigation a:hover {
            background: #fff1e8;

            color: #ff6b00;
        }


        .navigation .active a {
            background: #ff6b00;

            color: #ffffff;
        }


        /* =========================
           LOGOUT
        ========================= */

        .logout {
            margin-top: auto;

            padding: 15px;

            border-top: 1px solid #eeeeee;
        }


        .logout a {
            display: block;

            padding: 13px 16px;

            text-decoration: none;

            color: #555;

            font-size: 14px;

            border-radius: 9px;

            transition: 0.25s;
        }


        .logout a:hover {
            background: #fff1e8;

            color: #ff6b00;
        }


        /* =========================
           MAIN CONTENT
        ========================= */

        .main {
            margin-left: 245px;

            width: calc(100% - 245px);

            padding: 32px 38px;
        }


        /* =========================
           HEADER
        ========================= */

        .header {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 30px;
        }


        .welcome h1 {
            font-size: 27px;

            color: #222;

            margin-bottom: 7px;
        }


        .welcome p {
            color: #888;

            font-size: 14px;
        }


        /* =========================
           PROFILE
        ========================= */

        .profile {
            background: #ffffff;

            padding: 12px 17px;

            border: 1px solid #eeeeee;

            border-radius: 10px;
        }


        .profile-info strong {
            display: block;

            font-size: 14px;

            color: #222;
        }


        .profile-info small {
            color: #999;

            font-size: 12px;
        }


        /* =========================
           STATISTICS
        ========================= */

        .stats {
            display: grid;

            grid-template-columns: repeat(5, 1fr);

            gap: 17px;

            margin-bottom: 28px;
        }


        .stat-card {
            background: #ffffff;

            border: 1px solid #eeeeee;

            border-radius: 11px;

            padding: 22px;

            min-height: 120px;

            transition: 0.25s;
        }


        .stat-card:hover {
            transform: translateY(-3px);

            box-shadow:
                0 8px 20px rgba(0, 0, 0, 0.06);
        }


        .stat-content h4 {
            color: #888;

            font-size: 11px;

            font-weight: 600;

            margin-bottom: 12px;
        }


        .stat-content h2 {
            font-size: 26px;

            color: #222;
        }


        /* =========================
           REVENUE
        ========================= */

        .revenue-card {
            background: #ff6b00;

            border: none;
        }


        .revenue-card h4,
        .revenue-card h2 {
            color: #ffffff;
        }


        /* =========================
           RECENT ORDERS
        ========================= */

        .orders-box {
            background: #ffffff;

            border: 1px solid #eeeeee;

            border-radius: 12px;

            padding: 23px;

            width: 100%;
        }


        .box-header {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;
        }


        .box-header h2 {
            font-size: 18px;

            color: #222;
        }


        .view-all {
            text-decoration: none;

            color: #ff6b00;

            font-size: 13px;

            font-weight: 600;
        }


        /* =========================
           TABLE
        ========================= */

        .orders-table {
            width: 100%;

            border-collapse: collapse;
        }


        .orders-table th {
            text-align: left;

            font-size: 11px;

            color: #999;

            font-weight: 600;

            padding: 12px 8px;

            border-bottom: 1px solid #eeeeee;
        }


        .orders-table td {
            padding: 14px 8px;

            font-size: 13px;

            border-bottom: 1px solid #f2f2f2;
        }


        .orders-table tr:last-child td {
            border-bottom: none;
        }


        .order-id {
            font-weight: 600;

            color: #222;
        }


        /* =========================
           STATUS
        ========================= */

        .status {
            display: inline-block;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 600;
        }


        .status-pending {
            background: #fff4dc;

            color: #d58b00;
        }


        .status-completed {
            background: #e7f8ee;

            color: #1a9b50;
        }


        .status-cancelled {
            background: #ffe8e8;

            color: #d64545;
        }


        .status-default {
            background: #eeeeee;

            color: #666;
        }


        /* =========================
           EMPTY ORDERS
        ========================= */

        .no-orders {
            text-align: center;

            padding: 35px 10px;

            color: #999;

            font-size: 13px;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1200px) {

            .stats {
                grid-template-columns: repeat(3, 1fr);
            }

        }


        @media (max-width: 950px) {

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

        }


        @media (max-width: 750px) {

            .sidebar {
                width: 210px;
            }


            .main {
                margin-left: 210px;

                width: calc(100% - 210px);

                padding: 25px;
            }


            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

        }


        @media (max-width: 550px) {

            .sidebar {
                position: relative;

                width: 100%;

                height: auto;
            }


            .dashboard {
                display: block;
            }


            .main {
                margin-left: 0;

                width: 100%;
            }


            .stats {
                grid-template-columns: 1fr;
            }


            .header {
                flex-direction: column;

                align-items: flex-start;

                gap: 15px;
            }


            .orders-box {
                overflow-x: auto;
            }


            .orders-table {
                min-width: 600px;
            }

        }
        .status-form {
    margin: 0;
}

.status-select {
    padding: 6px 10px;
    border-radius: 20px;
    border: none;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    outline: none;
}

.status-select.status-pending {
    background: #fff4dc;
    color: #d58b00;
}

.status-select.status-completed {
    background: #e7f8ee;
    color: #1a9b50;
}

.status-select.status-cancelled {
    background: #ffe8e8;
    color: #d64545;
}

.status-select.status-default {
    background: #eeeeee;
    color: #666;
}

.status-form {
    margin: 0;
}

.status-select {
    padding: 7px 10px;
    border-radius: 20px;
    border: none;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    outline: none;
    background: #f1f1f1;
    color: #555;
}

.status-select:hover {
    opacity: 0.85;
}

    </style>

</head>


<body>


<div class="dashboard">


    <!-- =========================
         SIDEBAR
    ========================= -->

    <aside class="sidebar">


        <!-- LOGO -->

        <div class="logo">

            <h2>
                Crave<span>Bite</span>
            </h2>

        </div>


        <!-- ADMIN -->

        <div class="admin-name">
            Admin
        </div>


        <!-- NAVIGATION -->

        <nav class="navigation">

            <ul>

                <li class="active">

                    <a href="dashboard.php">
                        Dashboard
                    </a>

                </li>


                <li>

                    <a href="manage_food.php">
                        Food Management
                    </a>

                </li>


                <li>

                    <a href="order.php">
                        Orders
                    </a>

                </li>

                <li>

                    <a href="add_category.php">
                        Add Food Category
                    </a>

                </li>

            </ul>

        </nav>


        <!-- LOGOUT -->

        <div class="logout">

            <a href="../logout.php">
                Logout
            </a>

        </div>


    </aside>



    <!-- =========================
         MAIN CONTENT
    ========================= -->

    <main class="main">


        <!-- HEADER -->

        <header class="header">

            <div class="welcome">

                <h1>
                    Welcome back, Admin!
                </h1>

                <p>
                    Here's what's happening with CraveBite today.
                </p>

            </div>

        </header>



        <!-- =========================
             STATISTICS
        ========================= -->

        <section class="stats">


            <!-- CUSTOMERS -->

            <div class="stat-card">

                <div class="stat-content">

                    <h4>
                        TOTAL CUSTOMERS
                    </h4>

                    <h2>
                        <?php echo $total_customers; ?>
                    </h2>

                </div>

            </div>


            <!-- FOOD -->

            <div class="stat-card">

                <div class="stat-content">

                    <h4>
                        FOOD ITEMS
                    </h4>

                    <h2>
                        <?php echo $total_food; ?>
                    </h2>

                </div>

            </div>


            <!-- ORDERS -->

            <div class="stat-card">

                <div class="stat-content">

                    <h4>
                        TOTAL ORDERS
                    </h4>

                    <h2>
                        <?php echo $total_orders; ?>
                    </h2>

                </div>

            </div>


            <!-- PENDING -->

            <div class="stat-card">

                <div class="stat-content">

                    <h4>
                        PENDING ORDERS
                    </h4>

                    <h2>
                        <?php echo $pending_orders; ?>
                    </h2>

                </div>

            </div>


            <!-- REVENUE -->

            <div class="stat-card revenue-card">

                <div class="stat-content">

                    <h4>
                        TOTAL REVENUE
                    </h4>

                    <h2>
                        Rs.
                        <?php echo number_format($total_revenue, 0); ?>
                    </h2>

                </div>

            </div>


        </section>



        <!-- =========================
             RECENT ORDERS
        ========================= -->

        <section class="orders-box">


            <div class="box-header">

                <h2>
                    Recent Orders
                </h2>

                <a
                    href="order.php"
                    class="view-all"
                >
                    View All →
                </a>

            </div>



            <?php if ($recent_orders && mysqli_num_rows($recent_orders) > 0): ?>

<table class="orders-table">

    <thead>
        <tr>
            <th>ORDER</th>
            <th>CUSTOMER</th>
            <th>PHONE</th>
            <th>ITEMS</th>
            <th>AMOUNT</th>
            <th>STATUS</th>
            <th>DATE</th>
        </tr>
    </thead>

    <tbody>

    <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>

        <?php
            $status = $order['status'] ?? 'Pending';
            $status_class = 'status-default';

            if ($status == 'Pending') {
                $status_class = 'status-pending';
            } elseif ($status == 'Completed') {
                $status_class = 'status-completed';
            } elseif ($status == 'Cancelled') {
                $status_class = 'status-cancelled';
            }
        ?>

        <tr>

            <td class="order-id">
                #<?php echo $order['id']; ?>
            </td>

            <td>
                <?php echo htmlspecialchars($order['customer_name']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($order['phone_number']); ?>
            </td>

            <td style="max-width:220px; line-height:1.4;">
                <?php echo htmlspecialchars($order['food_items'] ?: 'No Items'); ?>
            </td>

            <td>
                Rs. <?php echo number_format($order['total_price'], 2); ?>
            </td>

            <td>
                <form method="POST" class="status-form">

                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">

                    <select
                        name="status"
                        class="status-select <?php echo $status_class; ?>"
                        onchange="this.form.submit()">

                        <option value="Pending" <?php echo ($status=='Pending')?'selected':''; ?>>Pending</option>
                        <option value="Confirmed" <?php echo ($status=='Confirmed')?'selected':''; ?>>Confirmed</option>
                        <option value="Preparing" <?php echo ($status=='Preparing')?'selected':''; ?>>Preparing</option>
                        <option value="Ready" <?php echo ($status=='Ready')?'selected':''; ?>>Ready</option>
                        <option value="Completed" <?php echo ($status=='Completed')?'selected':''; ?>>Completed</option>
                        <option value="Cancelled" <?php echo ($status=='Cancelled')?'selected':''; ?>>Cancelled</option>

                    </select>

                    <input type="hidden" name="update_status" value="1">

                </form>
            </td>

            <td>
                <?php echo date("M d, Y", strtotime($order['order_date'])); ?>
            </td>

        </tr>

    <?php endwhile; ?>

    </tbody>

</table>

<?php else: ?>

<div class="no-orders">
    No orders have been placed yet.
</div>

<?php endif; ?>


        </section>


    </main>


</div>


</body>

</html>

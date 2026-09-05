<?php

ob_start();
session_start();

include "../config/db.php";

/* ADMIN LOGIN CHECK */

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../login.php");
    exit();
}

$page = $_GET['page'] ?? 'dashboard';

$allowed_pages = [
    'dashboard',
    'food',
    'add_food',
    'categories',
    'orders'
];

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

/* UPDATE ORDER STATUS */

if (isset($_POST['update_status'])) {

    $order_id = (int)($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowed_statuses = [
        'Pending',
        'Confirmed',
        'Preparing',
        'Ready',
        'Completed',
        'Cancelled'
    ];

    if (
        $order_id > 0 &&
        in_array($status, $allowed_statuses)
    ) {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE orders SET status = ? WHERE id = ?"
        );

        if ($stmt) {

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

    header("Location: dashboard.php?page=orders");
    exit();
}

/* DASHBOARD STATISTICS */

// Total customers
$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'user'"
);

$customer_data = mysqli_fetch_assoc($result);
$total_customers = $customer_data['total'] ?? 0;


// Total food
$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM food"
);

$food_data = mysqli_fetch_assoc($result);
$total_food = $food_data['total'] ?? 0;


// Total categories
$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM category"
);

$category_data = mysqli_fetch_assoc($result);
$total_categories = $category_data['total'] ?? 0;


// Total orders
$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders"
);

$order_data = mysqli_fetch_assoc($result);
$total_orders = $order_data['total'] ?? 0;


// Total revenue
$result = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(total_price), 0) AS revenue
     FROM orders
     WHERE status != 'Cancelled'"
);

$revenue_data = mysqli_fetch_assoc($result);
$total_revenue = $revenue_data['revenue'] ?? 0;


// ----------------------------------------------------
// RECENT ORDERS
// ----------------------------------------------------

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

     JOIN users u
        ON o.user_id = u.id

     LEFT JOIN order_items oi
        ON o.id = oi.order_id

     LEFT JOIN food f
        ON oi.food_id = f.id

     GROUP BY
        o.id,
        o.total_price,
        o.status,
        o.order_date,
        o.phone_number,
        u.name

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

    <title>CraveBite Admin</title>

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

        /* MAIN LAYOUT */

        .admin-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }


        /* SIDEBAR */

        .sidebar {
            width: 230px;
            min-width: 230px;
            height: 100vh;

            position: fixed;
            left: 0;
            top: 0;

            background: #ffffff;

            border-right: 1px solid #e5e5e5;

            display: flex;
            flex-direction: column;

            z-index: 100;
        }


        /* LOGO */

        .logo {
            height: 115px;

            display: flex;
            align-items: center;

            padding-left: 35px;

            border-bottom: 1px solid #eeeeee;
        }

        .logo h2 {
            font-size: 30px;
            color: #ff6b00;
        }

        .logo span {
            color: #222;
        }


        /* ADMIN PANEL TITLE */

        .admin-title {
            padding: 25px 30px;

            text-align: center;

            border-bottom: 1px solid #eeeeee;

            font-size: 18px;
            font-weight: bold;
        }


        /* NAVIGATION */

        .navigation {
            padding: 25px 15px;
        }

        .navigation ul {
            list-style: none;
        }

        .navigation li {
            margin-bottom: 6px;
        }

        .navigation a {
            display: block;

            padding: 14px 18px;

            text-decoration: none;

            color: #444;

            font-size: 15px;

            border-radius: 10px;

            transition: 0.2s;
        }

        .navigation a:hover {
            background: #fff1e8;
            color: #ff6b00;
        }

        .navigation .active a {
            background: #ff6b00;
            color: white;
        }


        /* LOGOUT */

        .logout {
            margin-top: auto;

            padding: 18px 15px;

            border-top: 1px solid #eeeeee;
        }

        .logout a {
            display: block;

            padding: 14px 18px;

            text-decoration: none;

            color: #444;

            font-size: 15px;

            border-radius: 10px;

            transition: 0.2s;
        }

        .logout a:hover {
            background: #fff1e8;
            color: #ff6b00;
        }


        /* RIGHT SIDE*/

        .main-content {
            margin-left: 230px;

            width: calc(100% - 230px);

            min-height: 100vh;

            padding: 45px 50px;
        }

        .content-wrapper {
            width: 100%;
        }


        /* DASHBOARD WELCOME */

        .welcome {
            margin-bottom: 35px;
        }

        .welcome h1 {
            font-size: 34px;

            color: #222;

            margin-bottom: 8px;
        }

        .welcome p {
            font-size: 16px;

            color: #888;
        }


        /* STATISTICS */

        .stats {
            display: grid;

            grid-template-columns:
                repeat(5, minmax(160px, 1fr));

            gap: 22px;

            margin-bottom: 35px;
        }

        .stat-card {
            background: white;

            border: 1px solid #eeeeee;

            border-radius: 14px;

            padding: 28px;

            min-height: 145px;

            transition: 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);

            box-shadow:
                0 8px 25px rgba(0, 0, 0, 0.06);
        }

        .stat-content h4 {
            color: #888;

            font-size: 12px;

            margin-bottom: 15px;
        }

        .stat-content h2 {
            font-size: 32px;

            color: #222;
        }


        /* REVENUE */

        .revenue-card {
            background: #ff6b00;

            border: none;
        }

        .revenue-card h4,
        .revenue-card h2 {
            color: white;
        }


        /*RECENT ORDERS */

        .orders-box {
            background: white;

            border: 1px solid #eeeeee;

            border-radius: 14px;

            padding: 28px;

            width: 100%;

            overflow-x: auto;
        }

        .box-header {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 22px;
        }

        .box-header h2 {
            font-size: 21px;

            color: #222;
        }

        .view-all {
            text-decoration: none;

            color: #ff6b00;

            font-weight: bold;

            font-size: 14px;
        }

        .orders-table {
            width: 100%;

            border-collapse: collapse;

            min-width: 850px;
        }

        .orders-table th {
            text-align: left;

            font-size: 12px;

            color: #999;

            padding: 14px 10px;

            border-bottom: 1px solid #eeeeee;
        }

        .orders-table td {
            padding: 17px 10px;

            font-size: 14px;

            border-bottom: 1px solid #f2f2f2;
        }

        .order-id {
            font-weight: bold;
        }


        /* ==================================================
           STATUS
        ================================================== */

        .status-select {
            padding: 8px 12px;

            border: none;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;

            cursor: pointer;
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


        /* INNER PAGE */

        .inner-page {
            width: 100%;
        }


        /* RESPONSIVE */

        @media (max-width: 1200px) {

            .stats {
                grid-template-columns:
                    repeat(3, 1fr);
            }

        }


        @media (max-width: 800px) {

            .sidebar {
                width: 200px;
                min-width: 200px;
            }

            .main-content {
                margin-left: 200px;

                width: calc(100% - 200px);

                padding: 30px;
            }

            .stats {
                grid-template-columns:
                    repeat(2, 1fr);
            }

        }


        @media (max-width: 600px) {

            .sidebar {
                position: relative;

                width: 100%;

                min-width: 100%;

                height: auto;
            }

            .admin-layout {
                display: block;
            }

            .main-content {
                margin-left: 0;

                width: 100%;

                padding: 25px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>

<div class="admin-layout">


    <!-- LEFT SIDEBAR -->

    <aside class="sidebar">


        <!-- LOGO -->

        <div class="logo">

            <h2>
                Crave<span>Bite</span>
            </h2>

        </div>


        <!-- ADMIN PANEL -->

        <div class="admin-title">
            Admin Panel
        </div>


        <!-- NAVIGATION -->

        <nav class="navigation">

            <ul>


                <li class="<?php echo ($page == 'dashboard') ? 'active' : ''; ?>">

                    <a href="dashboard.php?page=dashboard">
                        Dashboard
                    </a>

                </li>


                <li class="<?php echo ($page == 'food') ? 'active' : ''; ?>">

                    <a href="dashboard.php?page=food">
                        Food Management
                    </a>

                </li>


                <li class="<?php echo ($page == 'add_food') ? 'active' : ''; ?>">

                    <a href="dashboard.php?page=add_food">
                        Add Food
                    </a>

                </li>


                <li class="<?php echo ($page == 'categories') ? 'active' : ''; ?>">

                    <a href="dashboard.php?page=categories">
                        Categories
                    </a>

                </li>


                <li class="<?php echo ($page == 'orders') ? 'active' : ''; ?>">

                    <a href="dashboard.php?page=orders">
                        Orders
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


    <!-- RIGHT CONTENT -->

    <main class="main-content">

        <div class="content-wrapper">


            <?php if ($page == 'dashboard'): ?>


                <!-- DASHBOARD -->

                <div class="welcome">

                    <h1>
                        Welcome back, Admin!
                    </h1>

                    <p>
                        Here's what's happening with CraveBite today.
                    </p>

                </div>


                <!-- STATISTICS -->

                <section class="stats">


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


                    <div class="stat-card">

                        <div class="stat-content">

                            <h4>
                                CATEGORIES
                            </h4>

                            <h2>
                                <?php echo $total_categories; ?>
                            </h2>

                        </div>

                    </div>


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


                    <div class="stat-card revenue-card">

                        <div class="stat-content">

                            <h4>
                                TOTAL REVENUE
                            </h4>

                            <h2>

                                Rs.
                                <?php
                                echo number_format(
                                    $total_revenue,
                                    0
                                );
                                ?>

                            </h2>

                        </div>

                    </div>


                </section>


                <!-- RECENT ORDERS -->

                <section class="orders-box">


                    <div class="box-header">

                        <h2>
                            Recent Orders
                        </h2>

                        <a
                            href="dashboard.php?page=orders"
                            class="view-all"
                        >
                            View All →
                        </a>

                    </div>


                    <?php if (
                        $recent_orders &&
                        mysqli_num_rows($recent_orders) > 0
                    ): ?>


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


                            <?php while (
                                $order =
                                mysqli_fetch_assoc($recent_orders)
                            ): ?>


                                <?php

                                $status =
                                    $order['status'] ?? 'Pending';

                                $status_class =
                                    'status-default';

                                if ($status == 'Pending') {

                                    $status_class =
                                        'status-pending';

                                } elseif ($status == 'Completed') {

                                    $status_class =
                                        'status-completed';

                                } elseif ($status == 'Cancelled') {

                                    $status_class =
                                        'status-cancelled';

                                }

                                ?>


                                <tr>


                                    <td class="order-id">

                                        #
                                        <?php
                                        echo $order['id'];
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $order['customer_name']
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $order['phone_number']
                                        );
                                        ?>

                                    </td>


                                    <td style="max-width:280px;">

                                        <?php
                                        echo htmlspecialchars(
                                            $order['food_items']
                                            ?: 'No Items'
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        Rs.

                                        <?php
                                        echo number_format(
                                            $order['total_price'],
                                            2
                                        );
                                        ?>

                                    </td>


                                    <td>


                                        <form
                                            method="POST"
                                            style="margin:0;"
                                        >


                                            <input
                                                type="hidden"
                                                name="order_id"
                                                value="<?php
                                                echo $order['id'];
                                                ?>"
                                            >


                                            <select
                                                name="status"
                                                class="status-select <?php echo $status_class; ?>"
                                                onchange="this.form.submit()"
                                            >


                                                <option
                                                    value="Pending"
                                                    <?php echo ($status == 'Pending') ? 'selected' : ''; ?>
                                                >
                                                    Pending
                                                </option>


                                                <option
                                                    value="Confirmed"
                                                    <?php echo ($status == 'Confirmed') ? 'selected' : ''; ?>
                                                >
                                                    Confirmed
                                                </option>


                                                <option
                                                    value="Preparing"
                                                    <?php echo ($status == 'Preparing') ? 'selected' : ''; ?>
                                                >
                                                    Preparing
                                                </option>


                                                <option
                                                    value="Ready"
                                                    <?php echo ($status == 'Ready') ? 'selected' : ''; ?>
                                                >
                                                    Ready
                                                </option>


                                                <option
                                                    value="Completed"
                                                    <?php echo ($status == 'Completed') ? 'selected' : ''; ?>
                                                >
                                                    Completed
                                                </option>


                                                <option
                                                    value="Cancelled"
                                                    <?php echo ($status == 'Cancelled') ? 'selected' : ''; ?>
                                                >
                                                    Cancelled
                                                </option>


                                            </select>


                                            <input
                                                type="hidden"
                                                name="update_status"
                                                value="1"
                                            >


                                        </form>


                                    </td>


                                    <td>

                                        <?php

                                        echo date(
                                            "M d, Y",
                                            strtotime(
                                                $order['order_date']
                                            )
                                        );

                                        ?>

                                    </td>


                                </tr>


                            <?php endwhile; ?>


                            </tbody>

                        </table>


                    <?php else: ?>


                        <p
                            style="
                                padding:40px;
                                text-align:center;
                                color:#999;
                            "
                        >
                            No orders have been placed yet.
                        </p>


                    <?php endif; ?>


                </section>


            <?php elseif ($page == 'food'): ?>


                <!-- FOOD MANAGEMENT -->

                <div class="inner-page">

                    <?php include "manage_food.php"; ?>

                </div>


            <?php elseif ($page == 'add_food'): ?>


                <!-- ADD FOOD -->

                <div class="inner-page">

                    <?php include "add_food.php"; ?>

                </div>


            <?php elseif ($page == 'categories'): ?>


                <!-- CATEGORIES -->

                <div class="inner-page">

                    <?php include "add_category.php"; ?>

                </div>


            <?php elseif ($page == 'orders'): ?>


                <!-- ORDERS-->

                <div class="inner-page">

                    <?php include "order.php"; ?>

                </div>


            <?php endif; ?>


        </div>

    </main>


</div>

</body>

</html>

<?php

ob_end_flush();

?>

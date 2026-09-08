<?php

ob_start();
session_start();

include "../config/db.php";

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

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'user'"
);

$customer_data = mysqli_fetch_assoc($result);
$total_customers = $customer_data['total'] ?? 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM food"
);

$food_data = mysqli_fetch_assoc($result);
$total_food = $food_data['total'] ?? 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM category"
);

$category_data = mysqli_fetch_assoc($result);
$total_categories = $category_data['total'] ?? 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders"
);

$order_data = mysqli_fetch_assoc($result);
$total_orders = $order_data['total'] ?? 0;

$result = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(total_price), 0) AS revenue
     FROM orders
     WHERE status != 'Cancelled'"
);

$revenue_data = mysqli_fetch_assoc($result);
$total_revenue = $revenue_data['revenue'] ?? 0;

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

    <link rel="stylesheet" href="../css/admin-dashboard.css">

</head>

<body>

<div class="admin-layout">

    <aside class="sidebar">

        <div class="logo">
            <h2>
                Crave<span>Bite</span>
            </h2>
        </div>

        <div class="admin-title">
            Admin Panel
        </div>

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

        <div class="logout">

            <a href="../logout.php">
                Logout
            </a>

        </div>

    </aside>

    <main class="main-content">

        <div class="content-wrapper">

            <?php if ($page == 'dashboard'): ?>

                <div class="welcome">

                    <h1>
                        Welcome back, Admin!
                    </h1>

                    <p>
                        Here's what's happening with CraveBite today.
                    </p>

                </div>

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

                                    <td class="food-items-cell">

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
                                            class="status-form"
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

                        <p class="no-orders">
                            No orders have been placed yet.
                        </p>

                    <?php endif; ?>

                </section>

            <?php elseif ($page == 'food'): ?>

                <div class="inner-page">
                    <?php include "manage_food.php"; ?>
                </div>

            <?php elseif ($page == 'add_food'): ?>

                <div class="inner-page">
                    <?php include "add_food.php"; ?>
                </div>

            <?php elseif ($page == 'categories'): ?>

                <div class="inner-page">
                    <?php include "add_category.php"; ?>
                </div>

            <?php elseif ($page == 'orders'): ?>

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
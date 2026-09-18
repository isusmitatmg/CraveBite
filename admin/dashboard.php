<?php
ob_start();
session_start();
include "../config/db.php";
require_once "../config/send_mail.php";

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
    'orders',
    'order_details',
    'customers',
    'reports'
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

    if ($order_id > 0 && in_array($status, $allowed_statuses)) {
        $customer_stmt = mysqli_prepare(
            $conn,
            "SELECT u.name, u.email, o.token
             FROM orders o
             JOIN users u ON o.user_id = u.id
             WHERE o.id = ?"
        );

        if ($customer_stmt) {
            mysqli_stmt_bind_param($customer_stmt, "i", $order_id);
            mysqli_stmt_execute($customer_stmt);
            $customer_result = mysqli_stmt_get_result($customer_stmt);
            $customer = mysqli_fetch_assoc($customer_result);
            mysqli_stmt_close($customer_stmt);

            $update_stmt = mysqli_prepare(
                $conn,
                "UPDATE orders SET status = ? WHERE id = ?"
            );

            if ($update_stmt) {
                mysqli_stmt_bind_param(
                    $update_stmt,
                    "si",
                    $status,
                    $order_id
                );

                if (mysqli_stmt_execute($update_stmt)) {
                    if ($customer) {
                        sendOrderStatusEmail(
                            $customer['email'],
                            $customer['name'],
                            $order_id,
                            $status,
                            $customer['token']
                        );
                    }
                }

                mysqli_stmt_close($update_stmt);
            }
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
    "SELECT COALESCE(SUM(total_price), 0) AS income
     FROM orders
     WHERE status = 'Completed'"
);
$income_data = mysqli_fetch_assoc($result);
$total_income = $income_data['income'] ?? 0;

$category_income = mysqli_query(
    $conn,
    "SELECT
        c.name AS category_name,
        COALESCE(
            SUM(
                CASE
                    WHEN o.status = 'Completed'
                    THEN oi.price * oi.quantity
                    ELSE 0
                END
            ),
            0
        ) AS income
     FROM category c
     LEFT JOIN food f
        ON c.id = f.category_id
     LEFT JOIN order_items oi
        ON f.id = oi.food_id
     LEFT JOIN orders o
        ON oi.order_id = o.id
     GROUP BY c.id, c.name
     ORDER BY income DESC, c.name ASC"
);

if (!$category_income) {
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
    <link rel="stylesheet" href="../css/admin-order-details.css">
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

                <li class="<?php echo ($page == 'customers') ? 'active' : ''; ?>">
                    <a href="dashboard.php?page=customers">
                        Customers
                    </a>
                </li>

                <li class="<?php echo ($page == 'reports') ? 'active' : ''; ?>">
                    <a href="dashboard.php?page=reports">
                        Reports
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
                                TOTAL INCOME
                            </h4>
                            <h2>
                                Rs.
                                <?php
                                echo number_format(
                                    $total_income,
                                    0
                                );
                                ?>
                            </h2>
                        </div>
                    </div>

                </section>

                <section class="orders-box income-section">

                    <div class="box-header">
                        <h2>
                            Income by Category
                        </h2>
                    </div>

                    <?php if (
                        $category_income &&
                        mysqli_num_rows($category_income) > 0
                    ): ?>

                        <table class="orders-table income-table">
                            <thead>
                                <tr>
                                    <th>
                                        CATEGORY
                                    </th>
                                    <th>
                                        INCOME
                                    </th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php
                            $category_total = 0;

                            while (
                                $category =
                                mysqli_fetch_assoc($category_income)
                            ):
                                $category_amount =
                                    (float)$category['income'];

                                $category_total +=
                                    $category_amount;
                            ?>

                                <tr>
                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $category['category_name']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        Rs.
                                        <?php
                                        echo number_format(
                                            $category_amount,
                                            2
                                        );
                                        ?>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                                <tr class="income-total">
                                    <td>
                                        <strong>
                                            TOTAL INCOME
                                        </strong>
                                    </td>

                                    <td>
                                        <strong>
                                            Rs.
                                            <?php
                                            echo number_format(
                                                $category_total,
                                                2
                                            );
                                            ?>
                                        </strong>
                                    </td>
                                </tr>

                            </tbody>
                        </table>

                    <?php else: ?>

                        <p class="no-orders">
                            No categories available.
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

            <?php elseif ($page == 'order_details'): ?>

                <div class="inner-page">
                    <?php include "order_details.php"; ?>
                </div>

            <?php elseif ($page == 'customers'): ?>

                <div class="inner-page">
                    <?php include "customers.php"; ?>
                </div>

            <?php elseif ($page == 'reports'): ?>

                <div class="inner-page">
                    <?php include "reports.php"; ?>
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
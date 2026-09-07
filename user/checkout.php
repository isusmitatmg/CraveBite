<?php

session_start();

include '../config/db.php';

// CHECK LOGIN
if (!isset($_SESSION['user_id'])) {

    // Remember that user wants to return to checkout
    $_SESSION['redirect_after_login'] = "user/checkout.php";

    // Send guest to register
    header("Location: ../register.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$order_placed = false;
$error = "";
$order_id = "";
$token_number = "";
$total_price = 0;
$item_count = 0;
$checkout_items = [];
$total = 0;

// PLACE ORDER
$phone_number = isset($_POST['phone'])
    ? trim($_POST['phone'])
    : '';

if (empty($phone_number)) {

} elseif (!preg_match('/^[0-9]{10}$/', $phone_number)) {

    $error = "Phone number must be exactly 10 digits.";

} else {

    // GET CART ITEMS
    $cart_query = "SELECT
                        cart.food_id,
                        cart.quantity,
                        food.price
                   FROM cart
                   JOIN food ON cart.food_id = food.id
                   WHERE cart.user_id = ?";

    $cart_stmt = mysqli_prepare($conn, $cart_query);

    if (!$cart_stmt) {
        die("Cart query failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $cart_stmt,
        "i",
        $user_id
    );

    mysqli_stmt_execute($cart_stmt);

    $cart_result = mysqli_stmt_get_result($cart_stmt);

    if (mysqli_num_rows($cart_result) == 0) {

        $error = "Your cart is empty.";

    } else {

        $total_price = 0;
        $item_count = 0;
        $cart_items = [];

        while ($item = mysqli_fetch_assoc($cart_result)) {

            $subtotal =
                $item['price'] * $item['quantity'];

            $total_price += $subtotal;
            $item_count += $item['quantity'];

            $cart_items[] = $item;
        }

        // GENERATE TOKEN
        $token_number = rand(100, 999);

        // START TRANSACTION
        mysqli_begin_transaction($conn);

        try {

            // INSERT ORDER
            $order_query = "INSERT INTO orders
                            (
                                user_id,
                                phone_number,
                                token,
                                total_price,
                                status,
                                order_date
                            )
                            VALUES
                            (?, ?, ?, ?, 'Pending', NOW())";

            $order_stmt = mysqli_prepare(
                $conn,
                $order_query
            );

            if (!$order_stmt) {
                throw new Exception(
                    "Order query failed: " .
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $order_stmt,
                "isid",
                $user_id,
                $phone_number,
                $token_number,
                $total_price
            );

            if (!mysqli_stmt_execute($order_stmt)) {
                throw new Exception(
                    "Order insert failed: " .
                    mysqli_stmt_error($order_stmt)
                );
            }

            $order_id = mysqli_insert_id($conn);

            mysqli_stmt_close($order_stmt);

            // INSERT ORDER ITEMS
            foreach ($cart_items as $item) {

                $item_query =
                    "INSERT INTO order_items
                    (
                        order_id,
                        food_id,
                        quantity,
                        price
                    )
                    VALUES (?, ?, ?, ?)";

                $item_stmt = mysqli_prepare(
                    $conn,
                    $item_query
                );

                if (!$item_stmt) {
                    throw new Exception(
                        "Order item query failed: " .
                        mysqli_error($conn)
                    );
                }

                mysqli_stmt_bind_param(
                    $item_stmt,
                    "iiid",
                    $order_id,
                    $item['food_id'],
                    $item['quantity'],
                    $item['price']
                );

                if (!mysqli_stmt_execute($item_stmt)) {
                    throw new Exception(
                        "Order item insert failed: " .
                        mysqli_stmt_error($item_stmt)
                    );
                }

                mysqli_stmt_close($item_stmt);
            }

            // CLEAR CART
            $clear_cart_query =
                "DELETE FROM cart
                 WHERE user_id = ?";

            $clear_stmt = mysqli_prepare(
                $conn,
                $clear_cart_query
            );

            if (!$clear_stmt) {
                throw new Exception(
                    "Clear cart query failed: " .
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $clear_stmt,
                "i",
                $user_id
            );

            if (!mysqli_stmt_execute($clear_stmt)) {
                throw new Exception(
                    "Could not clear cart."
                );
            }

            mysqli_stmt_close($clear_stmt);

            // SUCCESS
            mysqli_commit($conn);

            $order_placed = true;

        } catch (Exception $e) {

            mysqli_rollback($conn);

            $error = $e->getMessage();
        }
    }

    mysqli_stmt_close($cart_stmt);
}

// GET CART FOR CHECKOUT
if (!$order_placed) {

    $cart_query = "SELECT
                        cart.food_id,
                        cart.quantity,
                        food.name,
                        food.price,
                        food.image
                   FROM cart
                   JOIN food
                   ON cart.food_id = food.id
                   WHERE cart.user_id = ?";

    $cart_stmt = mysqli_prepare(
        $conn,
        $cart_query
    );

    if (!$cart_stmt) {
        die(
            "Cart query failed: " .
            mysqli_error($conn)
        );
    }

    mysqli_stmt_bind_param(
        $cart_stmt,
        "i",
        $user_id
    );

    mysqli_stmt_execute($cart_stmt);

    $result =
        mysqli_stmt_get_result($cart_stmt);

    $total = 0;
    $checkout_items = [];

    while ($row = mysqli_fetch_assoc($result)) {

        $total +=
            $row['price'] *
            $row['quantity'];

        $checkout_items[] = $row;
    }

    mysqli_stmt_close($cart_stmt);
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

    <title>

        <?php
        echo $order_placed
            ? 'Order Placed | CraveBite'
            : 'Checkout | CraveBite';
        ?>

    </title>

    <link
        rel="stylesheet"
        href="../css/checkout.css"
    >

</head>

<body>

<div class="checkout-page">

<?php if ($order_placed): ?>

    <div class="success-container">

        <div class="success-icon">
            🎉
        </div>

        <h1>
            Order Placed!
        </h1>

        <p>

            <?php echo $item_count; ?>

            item(s) ordered for

            <strong>
                Rs.

                <?php
                echo number_format(
                    $total_price,
                    2
                );
                ?>

            </strong>.

        </p>

        <p>

            Estimated preparation time:

            <strong>
                30–40 mins
            </strong>.

        </p>

        <div class="token-box">

            <div class="token-title">
                Your Token Number
            </div>

            <div class="token-number">

                #

                <?php
                echo htmlspecialchars(
                    $token_number
                );
                ?>

            </div>

        </div>

        <p class="order-id">

            Order ID:

            ORD-<?php
            echo htmlspecialchars(
                $order_id
            );
            ?>

        </p>

        <p>

            Please keep your token number.<br>

            We'll contact you when your order is ready.

        </p>

        <a
            href="../menu.php"
            class="continue-btn"
        >
            Continue Shopping
        </a>

        <br>

        <a
            href="../index.php"
            class="exit-btn"
        >
            Exit
        </a>

    </div>

<?php else: ?>

    <div class="checkout-header">

        <h1>
            Checkout
        </h1>

        <p>
            Confirm your details and place your pre-order.
        </p>

    </div>

    <?php if (!empty($error)): ?>

        <div class="error-message">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>

    <?php if (count($checkout_items) == 0): ?>

        <div class="checkout-box">

            <h2>
                Your cart is empty 🛒
            </h2>

            <p>
                Please add food to your cart before checking out.
            </p>

            <a
                href="../menu.php"
                class="continue-btn"
            >
                Browse Food
            </a>

            <br>

            <a
                href="../index.php"
                class="exit-btn"
            >
                Exit
            </a>

        </div>

    <?php else: ?>

        <div class="checkout-layout">

            <div class="checkout-box">

                <h3>
                    Contact Information
                </h3>

                <form
                    action="checkout.php"
                    method="POST"
                >

                    <div class="form-group">

                        <label>
                            Phone Number
                        </label>

                        <input
                            type="text"
                            name="phone"
                            placeholder="Enter 10-digit phone number"
                            maxlength="10"
                            pattern="[0-9]{10}"
                            inputmode="numeric"
                            required
                        >

                    </div>

                    <button
                        type="submit"
                        name="place_order"
                        class="place-order-btn"
                    >
                        Place Pre-Order
                    </button>

                </form>

            </div>

            <div class="checkout-summary">

                <h2>
                    Your Order
                </h2>

                <?php foreach ($checkout_items as $row): ?>

                    <div class="checkout-item">

                        <div>

                            <div class="checkout-item-name">

                                <?php
                                echo htmlspecialchars(
                                    $row['name']
                                );
                                ?>

                            </div>

                            <div class="checkout-item-quantity">

                                ×

                                <?php
                                echo $row['quantity'];
                                ?>

                            </div>

                        </div>

                        <strong>

                            Rs.

                            <?php
                            echo number_format(
                                $row['price'] *
                                $row['quantity'],
                                2
                            );
                            ?>

                        </strong>

                    </div>

                <?php endforeach; ?>

                <div class="summary-line">

                    <span>
                        Subtotal
                    </span>

                    <span>

                        Rs.

                        <?php
                        echo number_format(
                            $total,
                            2
                        );
                        ?>

                    </span>

                </div>

                <div class="divider"></div>

                <div class="total-line">

                    <span>
                        Total
                    </span>

                    <strong>

                        Rs.

                        <?php
                        echo number_format(
                            $total,
                            2
                        );
                        ?>

                    </strong>

                </div>

            </div>

        </div>

    <?php endif; ?>

<?php endif; ?>

</div>

</body>

</html>
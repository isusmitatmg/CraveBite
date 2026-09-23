<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = "user/checkout.php";
    header("Location: ../register.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$order_placed = false;
$error = "";
$order_id = "";
$token_number = "";
$total_price = 0;
$item_count = 0;
$checkout_items = [];
$total = 0;

$user_query = "SELECT phone FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);

if (!$user_stmt) {
    die("User query failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($user_stmt);

$phone_number = $user['phone'] ?? '';

if (empty($phone_number)) {
    $error = "Your phone number is not available in your account.";
}

if (isset($_POST['place_order']) && empty($error)) {
    $payment_method = $_POST['payment_method'] ?? '';

    if (!in_array($payment_method, ['Khalti', 'Pay at Restaurant'])) {
        $error = "Please select a payment method.";
    } else {
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

        mysqli_stmt_bind_param($cart_stmt, "i", $user_id);
        mysqli_stmt_execute($cart_stmt);
        $cart_result = mysqli_stmt_get_result($cart_stmt);

        if (mysqli_num_rows($cart_result) == 0) {
            $error = "Your cart is empty.";
        } else {
            $total_price = 0;
            $item_count = 0;
            $cart_items = [];

            while ($item = mysqli_fetch_assoc($cart_result)) {
                $subtotal = $item['price'] * $item['quantity'];
                $total_price += $subtotal;
                $item_count += $item['quantity'];
                $cart_items[] = $item;
            }

            $token_number = rand(100, 999);

            mysqli_begin_transaction($conn);

            try {
                $payment_status = 'Pending';

                $order_query = "INSERT INTO orders
                                (
                                    user_id,
                                    phone_number,
                                    token,
                                    total_price,
                                    status,
                                    order_date,
                                    payment_method,
                                    payment_status
                                )
                                VALUES
                                (?, ?, ?, ?, 'Pending', NOW(), ?, ?)";

                $order_stmt = mysqli_prepare($conn, $order_query);

                if (!$order_stmt) {
                    throw new Exception(
                        "Order query failed: " . mysqli_error($conn)
                    );
                }

                mysqli_stmt_bind_param(
                    $order_stmt,
                    "isidss",
                    $user_id,
                    $phone_number,
                    $token_number,
                    $total_price,
                    $payment_method,
                    $payment_status
                );

                if (!mysqli_stmt_execute($order_stmt)) {
                    throw new Exception(
                        "Order insert failed: " .
                        mysqli_stmt_error($order_stmt)
                    );
                }

                $order_id = mysqli_insert_id($conn);
                mysqli_stmt_close($order_stmt);

                foreach ($cart_items as $item) {
                    $item_query = "INSERT INTO order_items
                                   (
                                       order_id,
                                       food_id,
                                       quantity,
                                       price
                                   )
                                   VALUES (?, ?, ?, ?)";

                    $item_stmt = mysqli_prepare($conn, $item_query);

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

                mysqli_commit($conn);

                if ($payment_method === 'Khalti') {
                    echo '<form id="khaltiForm" action="../Khalti/khalti_pay.php" method="POST">';
                    echo '<input type="hidden" name="order_id" value="' .
                        htmlspecialchars($order_id) .
                        '">';
                    echo '</form>';
                    echo '<script>';
                    echo 'document.getElementById("khaltiForm").submit();';
                    echo '</script>';
                    exit();
                }

                $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
                $clear_stmt = mysqli_prepare($conn, $clear_cart_query);

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
                    throw new Exception("Could not clear cart.");
                }

                mysqli_stmt_close($clear_stmt);
                $order_placed = true;
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = $e->getMessage();
            }
        }

        mysqli_stmt_close($cart_stmt);
    }
}

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

    $cart_stmt = mysqli_prepare($conn, $cart_query);

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
    $result = mysqli_stmt_get_result($cart_stmt);

    $total = 0;
    $checkout_items = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $total += $row['price'] * $row['quantity'];
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
            href="menu.php"
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
    <a href="../menu.php" class="back-menu">
        ← Continue Shopping
    </a>

    <h1>Checkout</h1>

    <p class="subtitle">
        Complete your order and choose your payment method.
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
                Your cart is empty
            </h2>

            <p>
                Please add food to your cart before checking out.
            </p>

            <a
                href="menu.php"
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

                <form
                    action="checkout.php"
                    method="POST"
                >

                    <div class="payment-section">

                        <h3>
                            Payment Method
                        </h3>

                        <label class="payment-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="Khalti"
                                required
                            >

                            <span>
                                Pay with Khalti
                            </span>

                        </label>

                        <label class="payment-option">

                            <input
                                type="radio"
                                name="payment_method"
                                value="Pay at Restaurant"
                                required
                            >

                            <span>
                                Pay at Restaurant
                            </span>

                        </label>

                    </div>

                    <button
                        type="submit"
                        name="place_order"
                        class="place-order-btn"
                        id="placeOrderButton"
                    >
                        Place Pre-Order
                    </button>

                </form>

            </div>

        </div>

    <?php endif; ?>

<?php endif; ?>

</div>

<script>
const paymentOptions =
    document.querySelectorAll(
        'input[name="payment_method"]'
    );

const placeOrderButton =
    document.getElementById(
        'placeOrderButton'
    );

paymentOptions.forEach(function(option) {

    option.addEventListener(
        'change',
        function() {

            if (this.value === 'Khalti') {

                placeOrderButton.textContent =
                    'Place Order / Pay with Khalti';

            } else {

                placeOrderButton.textContent =
                    'Place Pre-Order';

            }

        }
    );

});
</script>

</body>
</html>

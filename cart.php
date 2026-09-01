
<?php

session_start();

include 'config/db.php';

$total = 0;
$cart_items = [];


/* LOGGED-IN USER CART */

if (isset($_SESSION['user_id'])) {

    $user_id = (int) $_SESSION['user_id'];

    $query = "SELECT 
                    cart.food_id,
                    cart.quantity,
                    food.name,
                    food.price,
                    food.image
              FROM cart
              JOIN food ON cart.food_id = food.id
              WHERE cart.user_id = ?";

    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        die("Cart query failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {

        $cart_items[] = $row;

        $total += $row['price'] * $row['quantity'];
    }

    mysqli_stmt_close($stmt);
}


/* GUEST CART */

else {

    if (
        isset($_SESSION['guest_cart']) &&
        !empty($_SESSION['guest_cart'])
    ) {

        foreach ($_SESSION['guest_cart'] as $food_id => $quantity) {

            $food_id = (int) $food_id;
            $quantity = (int) $quantity;

            if ($food_id <= 0 || $quantity <= 0) {
                continue;
            }

            $query = "SELECT 
                            id,
                            name,
                            price,
                            image
                      FROM food
                      WHERE id = ?";

            $stmt = mysqli_prepare($conn, $query);

            if (!$stmt) {
                die("Food query failed: " . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmt, "i", $food_id);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {

                $row['food_id'] = $food_id;
                $row['quantity'] = $quantity;

                $cart_items[] = $row;

                $total += $row['price'] * $quantity;
            }

            mysqli_stmt_close($stmt);
        }
    }
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

    <title>My Cart | CraveBite</title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>


<body>


<div class="cart-page">


    <!-- CART HEADER-->

    <div class="cart-header">

        <div>

            <p class="small-title">
                CRAVEBITE
            </p>

            <h1>
                My Cart 🛒
            </h1>

            <p class="subtitle">
                Review your items before placing your order.
            </p>

        </div>


        <a
            href="menu.php"
            class="back-menu"
        >
            ← Continue Shopping
        </a>

    </div>



    <!--  EMPTY CART -->

    <?php if (empty($cart_items)): ?>

        <div class="empty-cart">

            <div class="empty-icon">
                🛒
            </div>

            <h2>
                Your cart is empty
            </h2>

            <p>
                Looks like you haven't added anything yet.
            </p>

            <a
                href="menu.php"
                class="browse-btn"
            >
                Browse Food
            </a>

        </div>


    <?php else: ?>


        <!-- CART CONTENT-->

        <div class="cart-layout">


            <!-- CART ITEMS-->

            <div class="cart-items">


                <?php foreach ($cart_items as $row): ?>


                    <?php

                    $subtotal =
                        $row['price'] * $row['quantity'];

                    ?>


                    <div class="cart-item">


                        <!-- FOOD IMAGE -->

                        <div class="food-image">

                            <img
                                src="uploads/<?php echo htmlspecialchars($row['image']); ?>"
                                alt="<?php echo htmlspecialchars($row['name']); ?>"
                            >

                        </div>



                        <!-- FOOD DETAILS -->

                        <div class="food-details">

                            <h3>

                                <?php
                                echo htmlspecialchars($row['name']);
                                ?>

                            </h3>


                            <p class="price">

                                Rs.
                                <?php
                                echo number_format(
                                    $row['price'],
                                    2
                                );
                                ?>

                            </p>



                            <!-- QUANTITY -->

                            <div class="quantity-box">


                                <!-- DECREASE -->

                                <a
                                    href="user/update_cart.php?food_id=<?php echo $row['food_id']; ?>&action=decrease"
                                    class="quantity-btn"
                                >
                                    −
                                </a>


                                <!-- CURRENT QUANTITY -->

                                <span class="quantity">

                                    <?php
                                    echo $row['quantity'];
                                    ?>

                                </span>


                                <!-- INCREASE -->

                                <a
                                    href="user/update_cart.php?food_id=<?php echo $row['food_id']; ?>&action=increase"
                                    class="quantity-btn"
                                >
                                    +
                                </a>


                            </div>

                        </div>



                        <!-- ITEM TOTAL -->

                        <div class="item-right">


                            <p class="item-total">

                                Rs.

                                <?php
                                echo number_format(
                                    $subtotal,
                                    2
                                );
                                ?>

                            </p>


                            <!-- REMOVE -->

                            <a
                                href="user/remove_cart.php?food_id=<?php echo $row['food_id']; ?>"
                                class="remove-btn"
                                onclick="return confirm('Remove this item from your cart?');"
                            >
                                Remove
                            </a>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>



            <!-- ORDER SUMMARY -->

            <div class="order-summary">


                <h2>
                    Order Summary
                </h2>


                <!-- SUBTOTAL -->

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


            
                <!-- TOTAL -->

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


                <!--  CHECKOUT-->

                <a
                    href="user/checkout.php"
                    class="checkout-btn"
                >
                    Proceed to Checkout →
                </a>


                <!-- CONTINUE SHOPPING -->

                <a
                    href="menu.php"
                    class="continue-link"
                >
                    ← Continue Shopping
                </a>


            </div>


        </div>


    <?php endif; ?>


</div>


</body>

</html>
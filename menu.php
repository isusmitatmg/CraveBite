<?php
session_start();
include "config/db.php";

// Cart count
$cart_count = 0;

if (isset($_SESSION['user_id'])) {

    $user_id = (int)$_SESSION['user_id'];

    $count_query = mysqli_query(
        $conn,
        "SELECT SUM(quantity) AS total FROM cart WHERE user_id = $user_id"
    );

    if ($count_query) {
        $count = mysqli_fetch_assoc($count_query);
        $cart_count = (int)($count['total'] ?? 0);
    }

} elseif (isset($_SESSION['guest_cart'])) {

    $cart_count = array_sum($_SESSION['guest_cart']);
}

// Get food
$result = mysqli_query(
    $conn,
    "SELECT food.*, category.name AS category_name
     FROM food
     LEFT JOIN category
     ON food.category_id = category.id
     ORDER BY food.id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Food Menu | CraveBite</title>

    <link rel="stylesheet" href="css/style.css">

    <style>

        /* Quantity */
        .quantity-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 12px 0;
        }

        .quantity-box button {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 6px;
            background: #f97316;
            color: #fff;
            font-size: 18px;
            cursor: pointer;
        }

        .quantity-box button:hover {
            background: #ea580c;
        }

        .quantity-box input {
            width: 45px;
            height: 32px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        /* Food image */
        .food-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 10px;
        }

        /* Category */
        .category-name {
            color: #777;
            font-size: 14px;
        }

        /* Price */
        .food-price {
            color: #000;
            margin-top: 10px;
        }

        .food-price strong {
            color: #000;
            font-weight: bold;
            font-size: 18px;
        }

        /* Cart badge */
        .cart-link {
            position: relative;
            display: inline-block;
        }

        .cart-badge {
            position: absolute;
            top: -6px;
            right: -10px;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            background: #ef4444;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            line-height: 1;
            box-shadow: 0 2px 5px rgba(0,0,0,.25);
        }

    </style>
</head>

<body>

<!-- Navigation -->
<header>

    <nav class="navbar">

        <div class="brand">
            CraveBite
        </div>

        <ul class="nav-links">

            <li>
                <a href="index.php">Home</a>
            </li>

            <li>
                <a href="menu.php">Menu</a>
            </li>

            <?php if (isset($_SESSION['user_id'])): ?>

                <li>
                    <a href="user/my_order.php">My Orders</a>
                </li>

            <?php endif; ?>

            <!-- Cart -->
            <li>

                <a href="cart.php" class="cart-link">

                    Cart 🛒

                    <span
                        id="cart-badge"
                        class="cart-badge"
                        <?php
                        if ($cart_count == 0) {
                            echo 'style="display:none;"';
                        }
                        ?>
                    >
                        <?php echo $cart_count; ?>
                    </span>

                </a>

            </li>

            <?php if (isset($_SESSION['user_id'])): ?>

                <!-- Logged in -->
                <li>
                    <a href="logout.php">Logout</a>
                </li>

            <?php else: ?>

                <!-- Guest -->
                <li>
                    <a href="login.php">Login</a>
                </li>

                <li>
                    <a href="register.php">Register</a>
                </li>

            <?php endif; ?>

        </ul>

    </nav>

</header>


<!-- Food menu -->
<section class="popular">

    <h2>Our Menu</h2>

    <p class="section-subtitle">
        Choose your favorite food and add it to your cart.
    </p>

    <br>

    <div class="food-container">

        <?php while ($row = mysqli_fetch_assoc($result)): ?>

            <div class="food-card">

                <!-- Image -->
                <img
                    src="uploads/<?php echo htmlspecialchars($row['image']); ?>"
                    alt="<?php echo htmlspecialchars($row['name']); ?>"
                >

                <!-- Name -->
                <h3>
                    <?php echo htmlspecialchars($row['name']); ?>
                </h3>

                <!-- Description -->
                <p>
                    <?php echo htmlspecialchars($row['description']); ?>
                </p>

                <!-- Category -->
                <p class="category-name">
                    Category:
                    <?php
                    echo htmlspecialchars(
                        $row['category_name'] ?? 'Uncategorized'
                    );
                    ?>
                </p>

                <!-- Price -->
                <p class="food-price">
                    <br>

                    <strong>
                        Rs. <?php echo number_format($row['price'], 2); ?>
                    </strong>
                </p>

                <!-- Add to cart -->
                <form
                    class="add-cart-form"
                    method="POST"
                >

                    <input
                        type="hidden"
                        name="food_id"
                        value="<?php echo $row['id']; ?>"
                    >

                    <!-- Quantity -->
                    <div class="quantity-box">

                        <button
                            type="button"
                            onclick="decreaseQuantity(<?php echo $row['id']; ?>)"
                        >
                            −
                        </button>

                        <input
                            type="number"
                            id="quantity-<?php echo $row['id']; ?>"
                            name="quantity"
                            value="1"
                            min="1"
                            max="20"
                        >

                        <button
                            type="button"
                            onclick="increaseQuantity(<?php echo $row['id']; ?>)"
                        >
                            +
                        </button>

                    </div>

                    <!-- Add button -->
                    <button
                        type="submit"
                        class="main-btn"
                    >
                        Add to Cart
                    </button>

                </form>

            </div>

        <?php endwhile; ?>

    </div>

</section>


<script>

// Increase quantity
function increaseQuantity(id) {

    let input = document.getElementById("quantity-" + id);

    let current = parseInt(input.value) || 1;

    if (current < 20) {
        input.value = current + 1;
    }
}

// Decrease quantity
function decreaseQuantity(id) {

    let input = document.getElementById("quantity-" + id);

    let current = parseInt(input.value) || 1;

    if (current > 1) {
        input.value = current - 1;
    }
}

// Add to cart
document.querySelectorAll(".add-cart-form").forEach(function(form) {

    form.addEventListener("submit", function(e) {

        e.preventDefault();

        let quantityInput =
            form.querySelector('input[name="quantity"]');

        let qty =
            parseInt(quantityInput.value) || 1;

        // Keep quantity between 1 and 20
        if (qty < 1) {
            qty = 1;
            quantityInput.value = 1;
        }

        if (qty > 20) {
            qty = 20;
            quantityInput.value = 20;
        }

        // Send cart request
        fetch("add_to_cart.php", {
            method: "POST",
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            },
            body: new FormData(form)
        })

        .then(function(response) {

            if (!response.ok) {
                throw new Error("Failed to add item to cart");
            }

            return response.text();
        })

        .then(function() {

            // Update cart badge
            let badge =
                document.getElementById("cart-badge");

            let current =
                parseInt(badge.textContent) || 0;

            badge.textContent =
                current + qty;

            badge.style.display = "flex";
        })

        .catch(function(error) {

            console.error(error);

        });

    });

});

</script>

</body>
</html>

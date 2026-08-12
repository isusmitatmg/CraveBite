<?php

session_start();

include "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "user") {
    header("Location: ../login.php");
    exit();
}

/*
    Get food and category name together
*/
$result = mysqli_query($conn, "
    SELECT food.*, category.name AS category_name
    FROM food
    LEFT JOIN category ON food.category_id = category.id
");

if (!$result) {
    die("Food query failed: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Food Menu | CraveBite</title>

    <link rel="stylesheet" href="../css/style.css">

    <style>

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
            color: white;
            font-size: 18px;
            cursor: pointer;
        }

        .quantity-box input {
            width: 45px;
            height: 32px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .food-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 10px;
        }

    </style>

</head>


<body>


<header>

    <nav class="navbar">

        <div class="brand">
            CraveBite
        </div>

        <ul class="nav-links">

            <li>
                <a href="dashboard.php">
                    Dashboard
                </a>
            </li>

            <li>
                <a href="menu.php">
                    Menu
                </a>
            </li>

            <li>
                <a href="cart.php">
                    Cart 🛒
                </a>
            </li>

            <li>
                <a href="../logout.php">
                    Logout
                </a>
            </li>

        </ul>

    </nav>

</header>


<section class="popular">

    <h2>
        Our Menu
    </h2>


    <div class="food-container">


        <?php while ($row = mysqli_fetch_assoc($result)) { ?>


            <div class="food-card">


                <!-- Food Image -->

                <img
                    src="../uploads/<?php echo htmlspecialchars($row['image']); ?>"
                    alt="<?php echo htmlspecialchars($row['name']); ?>"
                >


                <!-- Food Name -->

                <h3>
                    <?php echo htmlspecialchars($row['name']); ?>
                </h3>


                <!-- Description -->

                <p>
                    <?php echo htmlspecialchars($row['description']); ?>
                </p>


                <!-- Category -->

                <p>
                    Category:
                    <?php echo htmlspecialchars($row['category_name']); ?>
                </p>


                <!-- Price -->

                <h4>
                    Rs. <?php echo number_format($row['price'], 2); ?>
                </h4>


                <!-- Quantity -->

                <form action="add_to_cart.php" method="POST">

                    <input
                        type="hidden"
                        name="food_id"
                        value="<?php echo $row['id']; ?>"
                    >


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


                    <button
                        type="submit"
                        name="add_to_cart"
                        class="main-btn"
                    >
                        Add to Cart
                    </button>

                </form>


            </div>


        <?php } ?>


    </div>

</section>


<script>

function increaseQuantity(id) {

    let input = document.getElementById("quantity-" + id);

    let current = parseInt(input.value);

    if (current < 20) {
        input.value = current + 1;
    }

}


function decreaseQuantity(id) {

    let input = document.getElementById("quantity-" + id);

    let current = parseInt(input.value);

    if (current > 1) {
        input.value = current - 1;
    }

}

</script>


</body>

</html>
<?php

session_start();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CraveBite | Online Food Pre-Order</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        .hero-image img {
            width: 100%;
            max-width: 520px;
            height: 430px;
            object-fit: cover;
            border-radius: 25px;
            display: block;
        }

        .hero-buttons {
            margin-top: 25px;
        }

        .section-subtitle {
            text-align: center;
            color: #777;
            margin-top: -10px;
            margin-bottom: 25px;
        }

        .nav-links .btn {
            padding: 9px 18px;
            border-radius: 7px;
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

            <?php if (isset($_SESSION['user_id'])): ?>

                <li>

                    <a href="user/my_order.php">

                        My Orders

                    </a>

                </li>

                <li>

                    <a href="logout.php" class="btn">

                        Logout

                    </a>

                </li>

            <?php else: ?>

                <li>

                    <a href="login.php" class="btn">

                        Login

                    </a>

                </li>

                <li>

                    <a href="register.php" class="btn">

                        Register

                    </a>

                </li>

            <?php endif; ?>

        </ul>

    </nav>

</header>

<section class="hero">

    <div class="hero-content">

        <h1>

            Your Favorite Food,

            <span>

                Just One Click Away

            </span>

        </h1>

        <p>

            Order delicious meals online and save your waiting time.

            Fresh food prepared just for you.

        </p>

        <div class="hero-buttons">

            <a href="menu.php" class="main-btn">

                Explore Menu

            </a>

        </div>

    </div>

    <div class="hero-image">

        <img

            src="uploads/cheeseburger.jpg"

            alt="Delicious Burger"

        >

    </div>

</section>

<section class="popular">

    <h2>

        Popular Food

    </h2>

    <p class="section-subtitle">

        Customer favorites you can pre-order from CraveBite.

    </p>

    <div class="food-container">

        <div class="food-card">

            <img

                src="uploads/burger.jpg"

                alt="Burger"

            >

            <h3>

                Classic Burger

            </h3>

            <p>

                Fresh and tasty classic burger

                with delicious fillings.

            </p>

            <a href="menu.php" class="main-btn">

                Order Now

            </a>

        </div>

        <div class="food-card">

            <img

                src="uploads/pizza.jpg"

                alt="Pizza"

            >

            <h3>

                Cheese Pizza

            </h3>

            <p>

                Hot and cheesy pizza made

                fresh for your order.

            </p>

            <a href="menu.php" class="main-btn">

                Order Now

            </a>

        </div>

        <div class="food-card">

            <img

                src="uploads/buffmomo.jpg"

                alt="Momo"

            >

            <h3>

                Buff Momo

            </h3>

            <p>

                Delicious steamed momos

                served fresh and hot.

            </p>

            <a href="menu.php" class="main-btn">

                Order Now

            </a>

        </div>

    </div>

</section>

<footer>

    <p>

        © 2026 CraveBite. All Rights Reserved.

    </p>

</footer>

</body>

</html>
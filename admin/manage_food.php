<?php
session_start();
include '../config/db.php';

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get food items
$sql = "SELECT 
            food.id,
            food.name,
            food.description,
            food.price,
            food.image,
            category.name AS category_name
        FROM food
        LEFT JOIN category ON food.category_id = category.id
        ORDER BY food.id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Food query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Food - CraveBite</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fff8f0;
            color: #333;
        }

        /* NAVBAR */

        .navbar {
            background: #f57c00;
            padding: 16px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h2 {
            margin: 0;
            color: white;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            margin-left: 20px;
        }

        .navbar a:hover {
            text-decoration: underline;
        }

        /* MAIN CONTAINER */

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            color: #f57c00;
            margin: 0;
        }

        .add-btn {
            background: #f57c00;
            color: white;
            text-decoration: none;
            padding: 11px 18px;
            border-radius: 7px;
            font-weight: bold;
        }

        .add-btn:hover {
            background: #e66d00;
        }

        /* FOOD GRID */

        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 25px;
        }

        /* FOOD CARD */

        .food-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.08);
            transition: 0.2s ease;
        }

        .food-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        /* IMAGE */

        .food-image {
            width: 100%;
            height: 190px;
            object-fit: cover;
            display: block;
        }

        .no-image {
            width: 100%;
            height: 190px;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
        }

        /* FOOD INFORMATION */

        .food-info {
            padding: 18px;
        }

        .food-info h3 {
            margin: 0 0 8px;
            font-size: 20px;
            color: #333;
        }

        .category {
            display: inline-block;
            background: #fff3e0;
            color: #f57c00;
            padding: 5px 9px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .description {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            min-height: 42px;
            margin-bottom: 12px;
        }

        .price {
            font-size: 18px;
            font-weight: bold;
            color: #f57c00;
            margin-bottom: 15px;
        }

        /* BUTTONS */

        .actions {
            display: flex;
            gap: 8px;
        }

        .edit-btn,
        .delete-btn {
            flex: 1;
            text-align: center;
            padding: 9px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }

        .edit-btn {
            background: #fff3cd;
            color: #856404;
        }

        .edit-btn:hover {
            background: #ffe69c;
        }

        .delete-btn {
            background: #f8d7da;
            color: #842029;
        }

        .delete-btn:hover {
            background: #f1b0b7;
        }

        /* EMPTY */

        .empty {
            background: white;
            padding: 50px;
            text-align: center;
            border-radius: 12px;
            color: #777;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.08);
        }

        /* MOBILE */

        @media (max-width: 600px) {

            .navbar {
                flex-direction: column;
                gap: 12px;
            }

            .navbar a {
                margin-left: 10px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .food-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

    <!-- NAVBAR -->

    <div class="navbar">

        <h2>CraveBite Admin</h2>

        <div>

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="../logout.php">
                Logout
            </a>

        </div>

    </div>


    <!-- MAIN -->

    <div class="container">

        <div class="page-header">

            <h1>
                Manage Food
            </h1>

            <a href="add_food.php" class="add-btn">
                + Add Food
            </a>

        </div>


        <?php if (mysqli_num_rows($result) > 0): ?>

            <div class="food-grid">

                <?php while ($row = mysqli_fetch_assoc($result)): ?>

                    <div class="food-card">


                        <!-- IMAGE -->

                        <?php if (!empty($row['image'])): ?>

                            <img
                                src="../uploads/<?php echo htmlspecialchars($row['image']); ?>"
                                alt="<?php echo htmlspecialchars($row['name']); ?>"
                                class="food-image"
                            >

                        <?php else: ?>

                            <div class="no-image">
                                No Image
                            </div>

                        <?php endif; ?>


                        <!-- FOOD INFORMATION -->

                        <div class="food-info">

                            <h3>
                                <?php echo htmlspecialchars($row['name']); ?>
                            </h3>


                            <span class="category">

                                <?php
                                echo htmlspecialchars(
                                    $row['category_name'] ?? 'No Category'
                                );
                                ?>

                            </span>


                            <div class="description">

                                <?php

                                $description = $row['description'] ?? '';

                                if ($description !== '') {

                                    echo htmlspecialchars($description);

                                } else {

                                    echo 'No description available.';

                                }

                                ?>

                            </div>


                            <div class="price">

                                Rs.
                                <?php
                                echo number_format(
                                    (float)$row['price'],
                                    2
                                );
                                ?>

                            </div>


                            <!-- ACTIONS -->

                            <div class="actions">

                                <a
                                    href="edit_food.php?id=<?php echo (int)$row['id']; ?>"
                                    class="edit-btn"
                                >
                                    Edit
                                </a>


                                <a
                                    href="delete_food.php?id=<?php echo (int)$row['id']; ?>"
                                    class="delete-btn"
                                    onclick="return confirm('Are you sure you want to delete this food?');"
                                >
                                    Delete
                                </a>

                            </div>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <div class="empty">

                <h3>
                    No Food Items Yet
                </h3>

                <p>
                    Add your first food item to start building your menu.
                </p>

                <a href="add_food.php" class="add-btn">
                    + Add Food
                </a>

            </div>

        <?php endif; ?>


    </div>

</body>

</html>
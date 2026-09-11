<?php

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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Food - CraveBite</title>

    <link rel="stylesheet" href="../css/admin-manage-food.css">

</head>

<body>

<div class="container">

    <div class="page-header">

        <h1>
            Manage Food
        </h1>

        <a
            href="dashboard.php?page=add_food"
            class="add-btn"
        >
            + Add Food
        </a>

    </div>

    <?php if (mysqli_num_rows($result) > 0): ?>

        <div class="food-grid">

            <?php while ($row = mysqli_fetch_assoc($result)): ?>

                <div class="food-card">

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

                    <div class="food-info">

                        <h3>
                            <?php
                            echo htmlspecialchars($row['name']);
                            ?>
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

                            $description =
                                $row['description'] ?? '';

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

            <a
                href="add_food.php"
                class="add-btn"
            >
                + Add Food
            </a>

        </div>

    <?php endif; ?>

</div>

</body>
</html>
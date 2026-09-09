<?php

include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

$message = "";

if (isset($_POST['add_category'])) {

    $name = trim($_POST['name']);

    if ($name == "") {

        $message = "Category name cannot be empty.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO category(name) VALUES (?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $name
        );

        if (mysqli_stmt_execute($stmt)) {

            $message = "Category Added Successfully!";

        } else {

            $message = "Error: " . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt);
    }
}

$categories = mysqli_query(
    $conn,
    "SELECT * FROM category ORDER BY name"
);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Add Category | CraveBite</title>

    <link
        rel="stylesheet"
        href="../css/admin-add-category.css"
    >

</head>

<body>

<div class="category-container">

    <h2>
        Add Food Category
    </h2>

    <?php if ($message != ""): ?>

        <p class="notice">
            <?php
            echo htmlspecialchars($message);
            ?>
        </p>

    <?php endif; ?>

    <div class="category-form-box">

        <form method="POST">

            <label>
                Category Name
            </label>

            <input
                type="text"
                name="name"
                placeholder="Enter category name"
                required
            >

            <button
                type="submit"
                name="add_category"
            >
                Add Category
            </button>

        </form>

    </div>

    <div class="category-list-box">

        <h3>
            Existing Categories
        </h3>

        <ul>

            <?php while ($row = mysqli_fetch_assoc($categories)): ?>

                <li>
                    <?php
                    echo htmlspecialchars($row['name']);
                    ?>
                </li>

            <?php endwhile; ?>

        </ul>

    </div>

</div>

</body>

</html>

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
        href="../css/style.css"
    >

    <style>

        body {
            margin: 0;
            background: #fff8f0;
            font-family: Arial, sans-serif;
            color: #333;
        }

        .category-container {
            width: 90%;
            max-width: 850px;
            margin: 40px auto;
        }

        .category-container h2 {
            color: #f57c00;
            margin-bottom: 20px;
        }

        .category-form-box,
        .category-list-box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        .category-form-box label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .category-form-box input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 7px;
            box-sizing: border-box;
            margin-bottom: 15px;
        }

        .category-form-box input:focus {
            outline: none;
            border-color: #f57c00;
        }

        .category-form-box button {
            background: #f57c00;
            color: white;
            border: none;
            padding: 11px 20px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 600;
        }

        .category-form-box button:hover {
            background: #e66d00;
        }

        .category-list-box h3 {
            margin-top: 0;
            color: #333;
        }

        .category-list-box ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .category-list-box li {
            padding: 13px 15px;
            margin-bottom: 8px;
            background: #fff8f0;
            border-left: 4px solid #f57c00;
            border-radius: 7px;
        }

        .notice {
            padding: 12px 15px;
            background: #fff3cd;
            color: #856404;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        @media (max-width: 600px) {

            .category-container {
                width: 92%;
                margin: 25px auto;
            }

        }

    </style>

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

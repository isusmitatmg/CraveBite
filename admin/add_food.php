<?php

session_start();

include "../config/db.php";


/* =========================
   ADMIN ONLY
========================= */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {

    header("Location: ../login.php");
    exit();

}


$message = "";


/* =========================
   GET CATEGORIES
========================= */

$category_query = "
    SELECT id, name
    FROM category
    ORDER BY name ASC
";

$category_result = mysqli_query(
    $conn,
    $category_query
);


if (!$category_result) {

    die(
        "Category query failed: " .
        mysqli_error($conn)
    );

}


/* =========================
   ADD FOOD
========================= */

if (isset($_POST['add_food'])) {


    $name = trim($_POST['name']);

    $description = trim($_POST['description']);

    $price = (float) $_POST['price'];

    /* IMPORTANT: get category ID */
    $category_id = (int) $_POST['category_id'];


    /* =========================
       CHECK CATEGORY
    ========================= */

    if ($category_id <= 0) {

        $message = "Please select a category.";

    }


    /* =========================
       IMAGE
    ========================= */

    elseif (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] == 0
    ) {


        $image = $_FILES['image']['name'];

        $temp_image = $_FILES['image']['tmp_name'];


        $folder = "../uploads/" . $image;


        if (!move_uploaded_file($temp_image, $folder)) {


            $message = "Failed to upload image.";


        } else {


            /* =========================
               INSERT FOOD
            ========================= */

            $query = "
                INSERT INTO food
                (
                    name,
                    description,
                    price,
                    image,
                    category_id
                )
                VALUES (?, ?, ?, ?, ?)
            ";


            $stmt = mysqli_prepare(
                $conn,
                $query
            );


            if ($stmt) {


                mysqli_stmt_bind_param(
                    $stmt,
                    "ssdsi",
                    $name,
                    $description,
                    $price,
                    $image,
                    $category_id
                );


                if (mysqli_stmt_execute($stmt)) {


                    $message =
                        "Food added successfully!";


                } else {


                    $message =
                        "Failed to add food: " .
                        mysqli_stmt_error($stmt);

                }


                mysqli_stmt_close($stmt);


            } else {


                $message =
                    "Database error: " .
                    mysqli_error($conn);

            }

        }


    } else {


        $message =
            "Please select a food image.";

    }


    /* =========================
       GET CATEGORIES AGAIN
    ========================= */

    $category_result = mysqli_query(
        $conn,
        "
        SELECT id, name
        FROM category
        ORDER BY name ASC
        "
    );

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

<title>Add Food | CraveBite</title>

<link
    rel="stylesheet"
    href="../css/style.css"
>


<style>

body {
    background: #fff8f0;
}


.form-container {

    width: 90%;

    max-width: 600px;

    margin: 40px auto;

    background: white;

    padding: 30px;

    border-radius: 15px;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.08);

}


.form-container h2 {

    color: #f57c00;

    margin-bottom: 25px;

}


.form-container label {

    display: block;

    margin-top: 15px;

    margin-bottom: 7px;

    font-weight: bold;

}


.form-container input,
.form-container textarea,
.form-container select {

    width: 100%;

    padding: 11px;

    border: 1px solid #ddd;

    border-radius: 7px;

    box-sizing: border-box;

    font-size: 15px;

}


.form-container textarea {

    min-height: 100px;

    resize: vertical;

}


.form-container input:focus,
.form-container textarea:focus,
.form-container select:focus {

    outline: none;

    border-color: #f57c00;

}


.form-container button {

    width: 100%;

    margin-top: 25px;

    padding: 13px;

    background: #f57c00;

    color: white;

    border: none;

    border-radius: 7px;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;

}


.form-container button:hover {

    background: #e66d00;

}


.message {

    padding: 12px;

    margin-bottom: 20px;

    background: #fff3cd;

    border-radius: 7px;

    color: #856404;

}


.navbar {

    background: #f57c00;

    padding: 15px 5%;

    display: flex;

    justify-content: space-between;

    align-items: center;

}


.navbar a {

    color: white;

    text-decoration: none;

    font-weight: bold;

}


.nav-links {

    display: flex;

    gap: 25px;

    list-style: none;

    margin: 0;

    padding: 0;

}

</style>

</head>


<body>


<header>

<nav class="navbar">


    <a href="dashboard.php">
        CraveBite Admin
    </a>


    <ul class="nav-links">


        <li>
            <a href="dashboard.php">
                Dashboard
            </a>
        </li>


        <li>
            <a href="manage_food.php">
                Manage Food
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



<div class="form-container">


    <h2>
        Add New Food
    </h2>


    <?php if (!empty($message)): ?>

        <div class="message">

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>



    <form
        method="POST"
        enctype="multipart/form-data"
    >


        <!-- FOOD NAME -->

        <label>
            Food Name
        </label>

        <input
            type="text"
            name="name"
            placeholder="Enter food name"
            required
        >



        <!-- DESCRIPTION -->

        <label>
            Description
        </label>

        <textarea
            name="description"
            placeholder="Enter food description"
            required
        ></textarea>



        <!-- PRICE -->

        <label>
            Price (Rs.)
        </label>

        <input
            type="number"
            name="price"
            placeholder="Enter price"
            min="1"
            required
        >



        <!-- CATEGORY -->

        <label>
            Category
        </label>


        <select
            name="category_id"
            required
        >


            <option value="">
                -- Select Category --
            </option>


            <?php while (
                $cat =
                mysqli_fetch_assoc($category_result)
            ): ?>


                <option
                    value="<?php
                        echo $cat['id'];
                    ?>"
                >

                    <?php
                    echo htmlspecialchars(
                        $cat['name']
                    );
                    ?>

                </option>


            <?php endwhile; ?>


        </select>



        <!-- IMAGE -->

        <label>
            Food Image
        </label>


        <input
            type="file"
            name="image"
            accept="image/*"
            required
        >



        <!-- BUTTON -->

        <button
            type="submit"
            name="add_food"
        >

            Add Food

        </button>


    </form>


</div>


</body>

</html>
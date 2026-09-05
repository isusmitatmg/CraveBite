<?php

include "../config/db.php";

/*ADMIN ONLY*/

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] != "admin"
) {
    header("Location: ../login.php");
    exit();
}

$message = "";

/* GET CATEGORIES */

$category_query = "
    SELECT id, name
    FROM category
    ORDER BY name ASC
";

$category_result = mysqli_query($conn, $category_query);

if (!$category_result) {
    die("Category query failed: " . mysqli_error($conn));
}

/* ADD FOOD */

if (isset($_POST['add_food'])) {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $price_unit = $_POST['price_unit'] ?? 'Per Plate';
    $category_id = (int)($_POST['category_id'] ?? 0);

    /*ALLOWED PRICE UNITS*/

    $allowed_units = [
        'Per Plate',
        'Per Unit'
    ];

    /* VALIDATION */

    if ($name === '') {

        $message = "Please enter the food name.";

    } elseif ($description === '') {

        $message = "Please enter the food description.";

    } elseif ($price < 50) {

        $message = "Price must be at least Rs. 50.";

    } elseif (!in_array($price_unit, $allowed_units)) {

        $message = "Please select a valid pricing unit.";

    } elseif ($category_id <= 0) {

        $message = "Please select a category.";

    }

    /* IMAGE */

    elseif (
        !isset($_FILES['image']) ||
        $_FILES['image']['error'] != 0
    ) {

        $message = "Please select a food image.";

    } else {

        $image = basename($_FILES['image']['name']);
        $temp_image = $_FILES['image']['tmp_name'];

        $upload_folder = "../uploads/";

        // Make sure uploads folder exists
        if (!is_dir($upload_folder)) {
            mkdir($upload_folder, 0777, true);
        }

        // Prevent duplicate filenames
        $extension = pathinfo($image, PATHINFO_EXTENSION);
        $filename = pathinfo($image, PATHINFO_FILENAME);

        $image = $filename . "_" . time() . "." . $extension;

        $folder = $upload_folder . $image;

        if (!move_uploaded_file($temp_image, $folder)) {

            $message = "Failed to upload image.";

        } else {

            /* INSERT FOOD */

            $query = "
                INSERT INTO food
                (
                    name,
                    description,
                    price,
                    price_unit,
                    image,
                    category_id
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ";

            $stmt = mysqli_prepare($conn, $query);

            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssdssi",
                    $name,
                    $description,
                    $price,
                    $price_unit,
                    $image,
                    $category_id
                );

                if (mysqli_stmt_execute($stmt)) {

                    $message = "Food added successfully!";

                } else {

                    $message =
                        "Failed to add food: " .
                        mysqli_stmt_error($stmt);

                    // Remove uploaded image if database insert failed
                    if (file_exists($folder)) {
                        unlink($folder);
                    }
                }

                mysqli_stmt_close($stmt);

            } else {

                $message =
                    "Database error: " .
                    mysqli_error($conn);

                // Remove uploaded image if statement failed
                if (file_exists($folder)) {
                    unlink($folder);
                }
            }
        }
    }

    /* GET CATEGORIES AGAIN */

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

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: #f7f7f7;
    font-family: Arial, Helvetica, sans-serif;
    color: #333;
}

.form-container {

    width: 100%;
    max-width: 700px;

    margin: 0 auto;

    background: white;

    padding: 35px;

    border-radius: 14px;

    border: 1px solid #eeeeee;
}

.form-container h2 {

    color: #222;

    margin-bottom: 25px;

    font-size: 28px;
}

.form-container label {

    display: block;

    margin-top: 17px;

    margin-bottom: 7px;

    font-weight: bold;

    font-size: 14px;

    color: #444;
}

.form-container input,
.form-container textarea,
.form-container select {

    width: 100%;

    padding: 12px;

    border: 1px solid #ddd;

    border-radius: 7px;

    font-size: 15px;

    background: white;
}

.form-container input:focus,
.form-container textarea:focus,
.form-container select:focus {

    outline: none;

    border-color: #ff6b00;
}

.form-container textarea {

    min-height: 110px;

    resize: vertical;
}

/* PRICE ROW */

.price-row {

    display: flex;

    gap: 12px;

    width: 100%;
}

.price-input {

    flex: 1;
}

.price-unit {

    width: 180px;
}

.price-row input,
.price-row select {

    width: 100%;
}

/* BUTTON */

.form-container button {

    width: 100%;

    margin-top: 28px;

    padding: 13px;

    background: #ff6b00;

    color: white;

    border: none;

    border-radius: 7px;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;
}

.form-container button:hover {

    background: #e65c00;
}

/* MESSAGE */

.message {

    padding: 12px;

    margin-bottom: 20px;

    background: #fff3cd;

    border-radius: 7px;

    color: #856404;

    border: 1px solid #ffe69c;
}

/* MOBILE */

@media (max-width: 600px) {

    .form-container {

        padding: 25px;
    }

    .price-row {

        flex-direction: column;
    }

    .price-unit {

        width: 100%;
    }
}

</style>

</head>

<body>

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


        <!-- PRICE + UNIT -->

        <label>
            Price
        </label>

        <div class="price-row">

            <div class="price-input">

                <input
                    type="number"
                    name="price"
                    min="50"
                    step="0.01"
                    placeholder="Enter price"
                    required
                >

            </div>

            <div class="price-unit">

                <select
                    name="price_unit"
                    required
                >

                    <option value="Per Plate">
                        Per Plate
                    </option>

                    <option value="Per Unit">
                        Per Unit
                    </option>

                </select>

            </div>

        </div>


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
                $cat = mysqli_fetch_assoc($category_result)
            ): ?>

                <option
                    value="<?php echo $cat['id']; ?>"
                >

                    <?php
                    echo htmlspecialchars($cat['name']);
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

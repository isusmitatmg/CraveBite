<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php?page=food");
    exit();
}

$id = (int) $_GET['id'];

$sql = "SELECT * FROM food WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$food = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$food) {
    header("Location: dashboard.php?page=food");
    exit();
}

$category_sql = "SELECT id, name FROM category ORDER BY name ASC";

$category_result = mysqli_query($conn, $category_sql);

if (!$category_result) {
    die("Category query failed: " . mysqli_error($conn));
}

if (isset($_POST['update_food'])) {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $category_id = (int) ($_POST['category_id'] ?? 0);

    $image = $food['image'];

    if ($name === '') {
        $error = "Food name is required.";
    } elseif ($description === '') {
        $error = "Food description is required.";
    } elseif ($price < 0) {
        $error = "Price cannot be negative.";
    } elseif ($category_id <= 0) {
        $error = "Please select a category.";
    }

    if (
        !isset($error) &&
        isset($_FILES['image']) &&
        $_FILES['image']['error'] === UPLOAD_ERR_OK
    ) {

        $image_name = $_FILES['image']['name'];
        $image_tmp = $_FILES['image']['tmp_name'];

        $extension = strtolower(
            pathinfo($image_name, PATHINFO_EXTENSION)
        );

        $allowed_extensions = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];

        if (!in_array($extension, $allowed_extensions)) {

            $error = "Only JPG, JPEG, PNG and WEBP images are allowed.";

        } else {

            $new_image =
                time() . '_' . uniqid() . '.' . $extension;

            $upload_folder = '../uploads/';

            if (!is_dir($upload_folder)) {
                mkdir($upload_folder, 0777, true);
            }

            if (
                move_uploaded_file(
                    $image_tmp,
                    $upload_folder . $new_image
                )
            ) {

                if (
                    !empty($food['image']) &&
                    file_exists($upload_folder . $food['image'])
                ) {
                    unlink($upload_folder . $food['image']);
                }

                $image = $new_image;

            } else {

                $error = "Failed to upload the new image.";
            }
        }
    }

    if (!isset($error)) {

        $update_sql = "
            UPDATE food
            SET
                name = ?,
                description = ?,
                price = ?,
                category_id = ?,
                image = ?
            WHERE id = ?
        ";

        $update_stmt = mysqli_prepare($conn, $update_sql);

        if (!$update_stmt) {
            die(
                "Update prepare failed: " .
                mysqli_error($conn)
            );
        }

        mysqli_stmt_bind_param(
            $update_stmt,
            "ssdisi",
            $name,
            $description,
            $price,
            $category_id,
            $image,
            $id
        );

        if (mysqli_stmt_execute($update_stmt)) {

            mysqli_stmt_close($update_stmt);

            header("Location: dashboard.php?page=food");
            exit();

        } else {

            $error =
                "Failed to update food: " .
                mysqli_stmt_error($update_stmt);

            mysqli_stmt_close($update_stmt);
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

    <title>Edit Food - CraveBite</title>

    <link
        rel="stylesheet"
        href="../css/admin-edit-food.css"
    >

</head>

<body>

<div class="navbar">

    <h2>CraveBite Admin</h2>

    <div>

        <a href="dashboard.php?page=food">
            Dashboard
        </a>

        <a href="../logout.php">
            Logout
        </a>

    </div>

</div>

<div class="container">

    <a
        href="dashboard.php?page=food"
        class="back-btn"
    >
        &larr; Back to Manage Food
    </a>

    <div class="form-box">

        <h1>
            Edit Food
        </h1>

        <?php if (isset($error)): ?>

            <div class="error">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>

        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <label for="name">
                Food Name
            </label>

            <input
                type="text"
                id="name"
                name="name"
                value="<?php
                    echo htmlspecialchars($food['name']);
                ?>"
                required
            >

            <label for="description">
                Description
            </label>

            <textarea
                id="description"
                name="description"
                required
            ><?php
                echo htmlspecialchars(
                    $food['description'] ?? ''
                );
            ?></textarea>

            <label for="price">
                Price
            </label>

            <input
                type="number"
                id="price"
                name="price"
                value="<?php
                    echo htmlspecialchars($food['price']);
                ?>"
                min="0"
                step="0.01"
                required
            >

            <label for="category_id">
                Category
            </label>

            <select
                id="category_id"
                name="category_id"
                required
            >

                <option value="">
                    Select Category
                </option>

                <?php while (
                    $category =
                    mysqli_fetch_assoc($category_result)
                ): ?>

                    <option
                        value="<?php
                            echo (int) $category['id'];
                        ?>"
                        <?php
                        echo (
                            (int) $category['id'] ===
                            (int) $food['category_id']
                        )
                            ? 'selected'
                            : '';
                        ?>
                    >

                        <?php
                        echo htmlspecialchars(
                            $category['name']
                        );
                        ?>

                    </option>

                <?php endwhile; ?>

            </select>

            <div class="current-image">

                <label>
                    Current Image
                </label>

                <?php if (!empty($food['image'])): ?>

                    <img
                        src="../uploads/<?php
                            echo htmlspecialchars(
                                $food['image']
                            );
                        ?>"
                        alt="Current Food Image"
                    >

                <?php else: ?>

                    <p>
                        No image uploaded.
                    </p>

                <?php endif; ?>

            </div>

            <label for="image">
                Change Image
            </label>

            <input
                type="file"
                id="image"
                name="image"
                accept=".jpg,.jpeg,.png,.webp"
            >

            <button
                type="submit"
                name="update_food"
                class="update-btn"
            >
                Update Food
            </button>

        </form>

    </div>

</div>

</body>

</html>
<?php
session_start();
include '../config/db.php';

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check food ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_food.php");
    exit();
}

$id = (int) $_GET['id'];

// Get food information
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

// Food not found
if (!$food) {
    header("Location: manage_food.php");
    exit();
}


// Get categories
$category_sql = "SELECT id, name FROM category ORDER BY name ASC";
$category_result = mysqli_query($conn, $category_sql);

if (!$category_result) {
    die("Category query failed: " . mysqli_error($conn));
}


// Update food
if (isset($_POST['update_food'])) {

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $category_id = (int) $_POST['category_id'];

    // Keep old image
    $image = $food['image'];

    // Check if new image was uploaded
    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] === UPLOAD_ERR_OK
    ) {

        $image_name = $_FILES['image']['name'];
        $image_tmp = $_FILES['image']['tmp_name'];

        $extension = strtolower(
            pathinfo($image_name, PATHINFO_EXTENSION)
        );

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($extension, $allowed_extensions)) {

            $error = "Only JPG, JPEG, PNG and WEBP images are allowed.";

        } else {

            $new_image = time() . '_' . uniqid() . '.' . $extension;

            $upload_folder = '../uploads/';

            if (!is_dir($upload_folder)) {
                mkdir($upload_folder, 0777, true);
            }

            if (move_uploaded_file($image_tmp, $upload_folder . $new_image)) {

                // Delete old image if it exists
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


    // Update database if there is no error
    if (!isset($error)) {

        $update_sql = "UPDATE food 
                       SET name = ?,
                           description = ?,
                           price = ?,
                           category_id = ?,
                           image = ?
                       WHERE id = ?";

        $update_stmt = mysqli_prepare($conn, $update_sql);

        if (!$update_stmt) {
            die("Update prepare failed: " . mysqli_error($conn));
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

            header("Location: manage_food.php");
            exit();

        } else {

            $error = "Failed to update food: " . mysqli_stmt_error($update_stmt);

            mysqli_stmt_close($update_stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Food - CraveBite</title>

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

       .container {
    width: 90%;
    max-width: 550px;
    margin: 30px auto;
}

.form-box {
    background: white;
    padding: 22px;
    border-radius: 10px;
    box-shadow: 0 5px 18px rgba(0, 0, 0, 0.08);
}
        h1 {
            color: #f57c00;
            margin-top: 0;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 11px;
            margin-bottom: 18px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #f57c00;
        }

        .current-image {
            margin-bottom: 18px;
        }

        .current-image img {
            width: 150px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            display: block;
            margin-top: 8px;
        }

        .update-btn {
            width: 100%;
            padding: 12px;
            background: #f57c00;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .update-btn:hover {
            background: #e66d00;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            color: #f57c00;
            text-decoration: none;
            font-weight: bold;
        }

        .error {
            background: #f8d7da;
            color: #842029;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

    </style>

</head>

<body>

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


    <div class="container">

        <a href="manage_food.php" class="back-btn">
            &larr; Back to Manage Food
        </a>


        <div class="form-box">

            <h1>
                Edit Food
            </h1>


            <?php if (isset($error)): ?>

                <div class="error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>


            <form method="POST" enctype="multipart/form-data">


                <!-- FOOD NAME -->

                <label for="name">
                    Food Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?php echo htmlspecialchars($food['name']); ?>"
                    required
                >


                <!-- DESCRIPTION -->

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    required
                ><?php echo htmlspecialchars($food['description'] ?? ''); ?></textarea>


                <!-- PRICE -->

                <label for="price">
                    Price
                </label>

                <input
                    type="number"
                    id="price"
                    name="price"
                    value="<?php echo htmlspecialchars($food['price']); ?>"
                    min="0"
                    step="0.01"
                    required
                >


                <!-- CATEGORY -->

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

                    <?php while ($category = mysqli_fetch_assoc($category_result)): ?>

                        <option
                            value="<?php echo (int)$category['id']; ?>"
                            <?php
                            echo ((int)$category['id'] === (int)$food['category_id'])
                                ? 'selected'
                                : '';
                            ?>
                        >
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>

                    <?php endwhile; ?>

                </select>


                <!-- CURRENT IMAGE -->

                <div class="current-image">

                    <label>
                        Current Image
                    </label>

                    <?php if (!empty($food['image'])): ?>

                        <img
                            src="../uploads/<?php echo htmlspecialchars($food['image']); ?>"
                            alt="Current Food Image"
                        >

                    <?php else: ?>

                        <p>
                            No image uploaded.
                        </p>

                    <?php endif; ?>

                </div>


                <!-- NEW IMAGE -->

                <label for="image">
                    Change Image
                </label>

                <input
                    type="file"
                    id="image"
                    name="image"
                    accept=".jpg,.jpeg,.png,.webp"
                >


                <!-- UPDATE -->

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
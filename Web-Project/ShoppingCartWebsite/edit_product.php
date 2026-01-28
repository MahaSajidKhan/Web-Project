<?php
session_start();
include "db.php";

// Block access if admin not logged in
if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit;
}

// Get product ID
if(!isset($_GET['id'])){
    header("Location: manage_products.php");
    exit;
}

$id = $_GET['id'];

// Fetch product details
$product_query = mysqli_query($conn, "SELECT * FROM products WHERE id = $id");
$product = mysqli_fetch_assoc($product_query);

// Fetch categories for dropdown
$cat_query = mysqli_query($conn, "SELECT * FROM categories");

// Update product when form submitted
if(isset($_POST['name'])){
    $name = $_POST['name'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    // ensure image column exists
    $colCheck = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'image'");
    if(!$colCheck || mysqli_num_rows($colCheck) == 0){
        mysqli_query($conn, "ALTER TABLE products ADD COLUMN image VARCHAR(255) NULL AFTER description");
    }

    // handle optional image upload
    $image_name = $product['image'] ?? null;
    if(isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE){
        $img = $_FILES['image'];
        if($img['error'] === UPLOAD_ERR_OK){
            $tmp = $img['tmp_name'];
            $info = @getimagesize($tmp);
            if($info === false){
                $error = 'Uploaded file is not a valid image.';
            } else {
                $ext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif'];
                if(!in_array($ext, $allowed)){
                    $error = 'Allowed image types: jpg, jpeg, png, gif.';
                } else {
                    $imgDir = __DIR__ . '/images';
                    if(!is_dir($imgDir)) mkdir($imgDir, 0755, true);
                    $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                    $dest = $imgDir . '/' . $newName;
                    if(move_uploaded_file($tmp, $dest)){
                        // delete old image if exists
                        if(!empty($image_name) && file_exists(__DIR__.'/images/'.$image_name)) unlink(__DIR__.'/images/'.$image_name);
                        $image_name = $newName;
                    } else {
                        $error = 'Failed to move uploaded image.';
                    }
                }
            }
        } else {
            $error = 'Image upload error.';
        }
    }

    if(!isset($error)){
        $name_s = mysqli_real_escape_string($conn, $name);
        $description_s = mysqli_real_escape_string($conn, $description);
        $price_f = floatval($price);
        $cat_i = intval($category);

        $update = "UPDATE products SET 
                    name='$name_s',
                    price=$price_f,
                    category_id=$cat_i,
                    description='$description_s',
                    image=" . ($image_name?"'".mysqli_real_escape_string($conn,$image_name)."'":"NULL") . "
                   WHERE id=$id";

        if(mysqli_query($conn, $update)){
            $success = "Product updated successfully!";
            // refresh product data
            $product_query = mysqli_query($conn, "SELECT * FROM products WHERE id = $id");
            $product = mysqli_fetch_assoc($product_query);
        } else {
            $error = "Error updating product: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Product</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container glass-card">
    <h2>Edit Product</h2>

    <?php if(isset($success)){ ?>
        <div class="msg"><?php echo $success; ?></div>
    <?php } ?>

    <?php if(isset($error)){ ?>
        <div class="error"><?php echo $error; ?></div>
    <?php } ?>

    <form method="POST" enctype="multipart/form-data" class="form-card">

        <div class="form-grid">
            <div class="form-row">
                <label class="form-label">Product Name</label>
                <div class="form-field"><input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required></div>
            </div>

            <div class="form-row">
                <label class="form-label">Price (PKR)</label>
                <div class="form-field"><input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required></div>
            </div>

            <div class="form-row">
                <label class="form-label">Category</label>
                <div class="form-field">
                    <select name="category" required>
                        <?php mysqli_data_seek($cat_query, 0); while($cat = mysqli_fetch_assoc($cat_query)){ ?>
                            <option value="<?php echo $cat['id']; ?>" <?php if($cat['id'] == $product['category_id']) echo "selected"; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="form-row form-row-full">
                <label class="form-label">Description</label>
                <div class="form-field"><textarea name="description" rows="5" required><?php echo htmlspecialchars($product['description']); ?></textarea></div>
            </div>

            <div class="form-row">
                <label class="form-label">Image</label>
                <div class="form-field">
                    <?php if(!empty($product['image']) && file_exists(__DIR__ . '/images/' . $product['image'])): ?>
                        <div class="image-preview"><img src="images/<?php echo htmlspecialchars($product['image']); ?>" alt="product"></div>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn-primary-custom" type="submit">Update Product</button>
            <a href="manage_products.php" class="btn-secondary">Cancel</a>
        </div>

    </form>
</div>

</body>
</html>

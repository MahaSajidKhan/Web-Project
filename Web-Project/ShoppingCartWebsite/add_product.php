<?php
session_start();
include "db.php";

// Block access if admin not logged in
if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit;
}

// Fetch categories from database
$cat_query = "SELECT * FROM categories";
$cat_run = mysqli_query($conn, $cat_query);

// When admin submits form
// Preserve submitted values so we can re-fill in case of error
$old = ['name'=>'','price'=>'','description'=>'','category_id'=>''];
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['name'], $_POST['price'], $_POST['category_id'], $_POST['description'])) {
        $old['name'] = trim($_POST['name']);
        $old['price'] = trim($_POST['price']);
        $old['category_id'] = trim($_POST['category_id']);
        $old['description'] = trim($_POST['description']);

        $name = mysqli_real_escape_string($conn, $old['name']);
        $price = floatval($old['price']);
        $category_id = intval($old['category_id']);
        $description = mysqli_real_escape_string($conn, $old['description']);

        // ensure products.image column exists (add if missing)
        $colCheck = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'image'");
        if(!$colCheck || mysqli_num_rows($colCheck) == 0){
            mysqli_query($conn, "ALTER TABLE products ADD COLUMN image VARCHAR(255) NULL AFTER description");
        }

        // handle image upload if provided
        $image_name = null;
        if(isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE){
            $img = $_FILES['image'];
            if($img['error'] === UPLOAD_ERR_OK){
                $tmp = $img['tmp_name'];
                $info = @getimagesize($tmp);
                if($info === false){
                    $error = 'Uploaded file is not a valid image.';
                } else {
                    $ext = image_type_to_extension($info[2]);
                    $ext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION)) ?: ltrim($ext, '.');
                    $allowed = ['jpg','jpeg','png','gif'];
                    if(!in_array($ext, $allowed)){
                        $error = 'Allowed image types: jpg, jpeg, png, gif.';
                    } else {
                        // ensure images directory exists
                        $imgDir = __DIR__ . '/images';
                        if(!is_dir($imgDir)) mkdir($imgDir, 0755, true);
                        $image_name = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                        $dest = $imgDir . '/' . $image_name;
                        if(!move_uploaded_file($tmp, $dest)){
                            $error = 'Failed to move uploaded image.';
                        }
                    }
                }
            } else {
                $error = 'Image upload error.';
            }
        }

        if(!isset($error) || $error === null){
            // Insert into database (include image column if present)
            if($image_name){
                $insert = "INSERT INTO products (name, price, category_id, description, image) VALUES ('$name', $price, $category_id, '$description', '".mysqli_real_escape_string($conn,$image_name)."')";
            } else {
                $insert = "INSERT INTO products (name, price, category_id, description) VALUES ('$name', $price, $category_id, '$description')";
            }

            if(mysqli_query($conn, $insert)){
                    $inserted_id = mysqli_insert_id($conn);
                    $success = "Product added successfully!";
                    // clear old values
                    $old = ['name'=>'','price'=>'','description'=>'','category_id'=>''];
                } else {
                    $error = "Error adding product: " . mysqli_error($conn);
                    // cleanup uploaded image on DB failure
                    if(!empty($image_name) && file_exists(__DIR__ . '/images/' . $image_name)) unlink(__DIR__ . '/images/' . $image_name);
                }
        }

    } else {
        $error = "Please fill all fields.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Product</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container addproduct-page">
    <div class="glass-card addproduct-card">
    <h2 class="checkout-title">Add New Product</h2>

    <?php if(isset($success)){ ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
            <?php if(isset($inserted_id)){ ?>
                &nbsp;<a href="product.php?id=<?php echo intval($inserted_id); ?>" style="margin-left:8px; font-weight:800;">View product</a>
                <a href="manage_products.php" style="margin-left:8px; font-weight:800;">Manage products</a>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if(isset($error)){ ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php } ?>

    <form method="POST" enctype="multipart/form-data" class="admin-form">
        <label>Product Name</label>
        <input type="text" name="name" required value="<?php echo htmlspecialchars($old['name']); ?>">

            <div class="field">
                <label>Price</label>
                <input type="number" name="price" step="0.01" required value="<?php echo htmlspecialchars($old['price']); ?>">
            </div>

            <div class="field">
                <label>Description</label>
                <textarea name="description" required rows="6"><?php echo htmlspecialchars($old['description']); ?></textarea>
            </div>

            <div class="field">
                <label>Category</label>
                <select name="category_id" required>
                    <option value="">--Select Category--</option>
                    <?php mysqli_data_seek($cat_run, 0); while($cat = mysqli_fetch_assoc($cat_run)){ ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($old['category_id']==$cat['id'])? 'selected':''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="field file-field">
                <label>Image (optional)</label>
                <input type="file" name="image" id="imageInput" accept="image/*">
                <div id="imagePreview" class="image-preview">
                    <span class="muted">Image preview will appear here</span>
                </div>
            </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary-custom">Add Product</button>
        </div>
    </form>
  </div>
</div>

<script>
document.getElementById('imageInput')?.addEventListener('change', function(e){
    var file = this.files && this.files[0];
    var preview = document.getElementById('imagePreview');
    if(!file){ preview.innerHTML = '<span class="muted">Image preview will appear here</span>'; preview.classList.remove('has-image'); return; }
    var img = document.createElement('img');
    img.style.maxWidth = '100%'; img.style.maxHeight = '100%'; img.style.width = 'auto'; img.style.height = 'auto'; img.style.objectFit = 'contain';
    var reader = new FileReader();
    reader.onload = function(ev){ img.src = ev.target.result; preview.innerHTML=''; preview.appendChild(img); preview.classList.add('has-image'); };
    reader.readAsDataURL(file);
});
</script>

</body>
</html>
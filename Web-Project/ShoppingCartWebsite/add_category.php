<?php
session_start();
include "db.php";

// Block access if admin not logged in
if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    if($name === ''){
        $error = 'Category name is required.';
    } else {
        // ensure categories.category_image column exists (optional)
        $colCheck = mysqli_query($conn, "SHOW COLUMNS FROM categories LIKE 'category_image'");
        if(!$colCheck || mysqli_num_rows($colCheck) == 0){
            @mysqli_query($conn, "ALTER TABLE categories ADD COLUMN category_image VARCHAR(255) NULL AFTER name");
        }

        // handle optional image upload
        $image_name = null;
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
                        $imgDir = __DIR__ . '/category_images';
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

        if(!isset($error)){
            $n = mysqli_real_escape_string($conn, $name);
            $d = mysqli_real_escape_string($conn, $description);
            // detect description column once and build SQL accordingly (avoid referencing missing column)
            $descCheck = mysqli_query($conn, "SHOW COLUMNS FROM categories LIKE 'description'");
            $hasDesc = ($descCheck && mysqli_num_rows($descCheck) > 0);
            if($image_name){
                $imgEsc = mysqli_real_escape_string($conn, $image_name);
                if($hasDesc){
                    $sql = "INSERT INTO categories (name, description, category_image) VALUES ('$n', '$d', '$imgEsc')";
                } else {
                    $sql = "INSERT INTO categories (name, category_image) VALUES ('$n', '$imgEsc')";
                }
            } else {
                if($hasDesc){
                    $sql = "INSERT INTO categories (name, description) VALUES ('$n', '$d')";
                } else {
                    $sql = "INSERT INTO categories (name) VALUES ('$n')";
                }
            }

            if(mysqli_query($conn, $sql)){
                $success = 'Category added successfully.';
                // redirect to categories list after short delay
                header("Location: categories_list.php?msg=cat_added");
                exit;
            } else {
                $error = 'DB error: ' . mysqli_error($conn);
                if(!empty($image_name) && file_exists(__DIR__ . '/images/' . $image_name)) unlink(__DIR__ . '/images/' . $image_name);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Category</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container addcategory-page">
    <div class="glass-card addcategory-card">
        <h2 class="checkout-title">Add New Category</h2>

        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="admin-form">
            <div class="field">
                <label>Category Name</label>
                <input type="text" name="name" required>
            </div>

            <div class="field">
                <label>Description (optional)</label>
                <textarea name="description"></textarea>
            </div>

            <div class="field file-field">
                <label>Image (optional)</label>
                <input type="file" name="image" id="catImageInput" accept="image/*">
                <div id="catImagePreview" class="image-preview">
                    <span class="muted">Image preview will appear here</span>
                </div>
            </div>

                <script>
                document.getElementById('catImageInput')?.addEventListener('change', function(e){
                    var file = this.files && this.files[0];
                    var preview = document.getElementById('catImagePreview');
                    if(!file){ preview.innerHTML = '<span class="muted">Image preview will appear here</span>'; preview.classList.remove('has-image'); return; }
                    var img = document.createElement('img');
                    img.style.maxWidth = '100%'; img.style.maxHeight = '100%'; img.style.width = 'auto'; img.style.height = 'auto'; img.style.objectFit = 'contain';
                    var reader = new FileReader();
                    reader.onload = function(ev){ img.src = ev.target.result; preview.innerHTML=''; preview.appendChild(img); preview.classList.add('has-image'); };
                    reader.readAsDataURL(file);
                });
                </script>

            <div class="form-actions">
                <button type="submit" class="btn-primary-custom">Create Category</button>
                <a class="btn-cancel" href="admin_dashboard.php">Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>

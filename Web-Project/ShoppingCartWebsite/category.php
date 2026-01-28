<?php 
include "db.php";

// Get category ID from URL
$category_id = $_GET['id'];

// Get category name + image (handle optional column)
$hasImage = false;
$colCheckImg = $conn->query("SHOW COLUMNS FROM categories LIKE 'category_image'");
if($colCheckImg && $colCheckImg->num_rows > 0) $hasImage = true;

if($hasImage){
    $cat_query = "SELECT name, category_image FROM categories WHERE id = $category_id";
} else {
    $cat_query = "SELECT name FROM categories WHERE id = $category_id";
}

$cat_run = mysqli_query($conn, $cat_query);
$cat_row = mysqli_fetch_assoc($cat_run);

$category_name = $cat_row['name'];
$category_image = ($hasImage && isset($cat_row['category_image'])) ? trim($cat_row['category_image']) : '';

// Absolute URL path for category image with fallback
// Use relative URL for category image so it works under different site roots
$cat_img_path = 'category_images/' . $category_image;
if (empty($category_image) || !file_exists(__DIR__ . '/category_images/' . $category_image)) {
    $cat_img_path = 'category_images/default.jpg';
    if (!file_exists(__DIR__ . '/category_images/default.jpg')) {
        $cat_img_path = 'assets/css/placeholder.png';
    }
}

// Get all products in this category
$prod_query = "SELECT * FROM products WHERE category_id = $category_id";
$prod_run = mysqli_query($conn, $prod_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($category_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
        }
    </style>
</head>
<body>

<div class="header-bar"><?php echo htmlspecialchars($category_name); ?></div>

<div class="container">

    <!-- Category Display Image -->
    <div style="text-align:center; margin-bottom:20px;">
        <img src="<?php echo htmlspecialchars($cat_img_path); ?>" 
             alt="<?php echo htmlspecialchars($category_name); ?>" 
             style="max-width:300px; border-radius:10px;">
    </div>

    <h3>Products</h3>

    <div class="products-grid">
        <?php
        if (mysqli_num_rows($prod_run) > 0) {
            while ($row = mysqli_fetch_assoc($prod_run)) {

                // Relative path for product image
                $img_path = 'images/' . trim($row['image']);
        ?>
                <div class='glass-card product-card'>
                    <a href="product.php?id=<?php echo $row['id']; ?>" title="View <?php echo htmlspecialchars($row['name']); ?>">
                        <img src="<?php echo htmlspecialchars($img_path); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                    </a>
                    <div class='meta'>
                        <h2><?php echo htmlspecialchars($row['name']); ?></h2>
                        <p>Price: <span class='price'><?php echo $row['price']; ?> PKR</span></p>
                        <a class='btn-primary-custom' href='product.php?id=<?php echo $row['id']; ?>'>View Details</a>
                    </div>
                </div>
        <?php
            }
        } else {
            echo "<p>No products available in this category.</p>";
        }
        ?>
    </div>

</div>

</body>
</html>
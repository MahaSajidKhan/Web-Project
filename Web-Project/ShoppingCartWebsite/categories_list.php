<?php 
include "db.php";

// Fetch all categories
$categories = mysqli_query($conn, "SELECT * FROM categories");
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Categories</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .category-card {
            text-align: center;
            padding: 15px;
            border-radius: 12px;
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: inherit;
        }
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .category-card img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 10px;
            display: block;
        }
        .category-card span {
            display: block;
            font-size: 18px;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>All Categories</h2>

    <div class="categories-grid">
        <?php while($row = mysqli_fetch_assoc($categories)): ?>
            <?php
                $img = isset($row['category_image']) ? trim($row['category_image']) : '';
                // use relative URL so the site works regardless of virtual directory
                $path = 'category_images/' . $img;

                if (empty($img) || !file_exists(__DIR__ . '/category_images/' . $img)) {
                    // fallback to relative default image if present
                    $path = 'category_images/default.jpg';
                    if (!file_exists(__DIR__ . '/category_images/default.jpg')) {
                        $path = 'assets/css/placeholder.png'; // optional final fallback (may not exist)
                    }
                }
            ?>
            <a href="category.php?id=<?= $row['id'] ?>" class="category-card">
                <img src="<?= htmlspecialchars($path) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                <span><?= htmlspecialchars($row['name']) ?></span>
            </a>
        <?php endwhile; ?>
    </div>
</div>

</body>
</html>
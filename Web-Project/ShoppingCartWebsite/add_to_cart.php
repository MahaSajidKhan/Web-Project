<?php
session_start();
include "db.php";

// Check if product ID is passed
if (!isset($_GET['id'])) {
    echo "<script>
            alert('No product ID specified.');
            window.location.href='index.php';
          </script>";
    exit;
}

$product_id = intval($_GET['id']);  // Sanitize input to integer

// Fetch product details from database
$query = "SELECT * FROM products WHERE id = $product_id";
$run = mysqli_query($conn, $query);

if (!$run) {
    // Query error handling
    echo "<script>
            alert('Database error. Please try again later.');
            window.location.href='index.php';
          </script>";
    exit;
}

$product = mysqli_fetch_assoc($run);

if (!$product) {
    // Product not found
    echo "<script>
            alert('Product not found.');
            window.location.href='index.php';
          </script>";
    exit;
}

// Initialize cart if not already set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add product to cart or increase quantity if already added
if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id]['quantity'] += 1;
} else {
    $_SESSION['cart'][$product_id] = [
        'name' => $product['name'],
        'price' => $product['price'],
        'quantity' => 1
    ];
}

// Success message and redirect to cart page
echo "<script>
        alert('Product added to cart!');
        window.location.href='cart.php';
      </script>";
exit;
?>

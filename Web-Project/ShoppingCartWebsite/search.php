<?php
include 'db.php'; // make sure this connects to your database
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the search query
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// If the user typed nothing
if ($q === "") {
    echo "<p style='text-align:center; margin-top:20px;'>Please type something to search.</p>";
    echo "<p style='text-align:center;'><a href='index.php'>Back to Home</a></p>";
    exit;
}

// Escape the query to prevent SQL injection
$q_safe = mysqli_real_escape_string($conn, $q);

// store last search in session for simple personalization on the homepage
if(!empty($q)){
    $_SESSION['last_search'] = $q;
}

// Search in the products table
$sql = "SELECT * FROM products 
        WHERE name LIKE '%$q_safe%' 
        OR description LIKE '%$q_safe%'";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Results</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">
  <h2>Search results for: "<?php echo htmlspecialchars($q); ?>"</h2>
  <p><a href="index.php">Back to Home</a></p>
  <hr>

<?php
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $img = "images/" . $row['image']; // make sure images are in images/ folder

                echo "<div class='glass-card product-card mb-3'>
                                <a href='product.php?id=".$row['id']."' title='View " . htmlspecialchars(
                                    $row['name']) . "'>
                                    <img src='".$img."' alt='".htmlspecialchars($row['name'])."'>
                                </a>
                                <div class='meta'>
                                    <h3>".htmlspecialchars($row['name'])."</h3>
                                    <p>Price: <span class='price'>".htmlspecialchars($row['price'])." PKR</span></p>
                                    <a class='btn-primary-custom' href='product.php?id=".$row['id']."'>View Product</a>
                                </div>
                            </div>";
    }
} else {
    echo "<p>No products found.</p>";
}
?>

</body>
</html>

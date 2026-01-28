<?php
session_start();
include "db.php";

// Block access if admin not logged in
if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit;
}

// Delete product
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM products WHERE id = $id");
    header("Location: manage_products.php");
    exit;
}

// Fetch all products
$query = "SELECT products.*, categories.name AS category_name 
          FROM products 
          LEFT JOIN categories ON products.category_id = categories.id";
$run = mysqli_query($conn, $query);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Products</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container glass-card">
    <h2>Manage Products</h2>

    <table class="manage-table table-bordered hoverable" aria-describedby="products-list">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Price (PKR)</th>
            <th>Category</th>
            <th>Description</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php while($p = mysqli_fetch_assoc($run)) { ?>
        <tr>
            <td data-label="ID"><?php echo $p['id']; ?></td>
            <td data-label="Name"><?php echo htmlspecialchars($p['name']); ?></td>
            <td data-label="Price"><?php echo htmlspecialchars($p['price']); ?></td>
            <td data-label="Category"><?php echo htmlspecialchars($p['category_name']); ?></td>
            <td data-label="Description"><span class="desc-clamp"><?php echo htmlspecialchars($p['description']); ?></span></td>
            <td data-label="Actions">
                <div class="actions">
                    <a class="btn-primary-custom" href="edit_product.php?id=<?php echo $p['id']; ?>">Edit</a>
                    <a class="btn-primary-custom" style="background:crimson; color:white;" href="manage_products.php?delete=<?php echo $p['id']; ?>" onclick="return confirm('Delete product #<?php echo $p['id']; ?>?');">Delete</a>
                </div>
            </td>
        </tr>
        <?php } ?>
        </tbody>
    </table>

</div>

</body>
</html>

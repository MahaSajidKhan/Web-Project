<?php
session_start();
if(!isset($_SESSION['user_id'])){ header('Location: user_login.php'); exit; }
require_once 'db.php';

$uid = intval($_SESSION['user_id']);

// ensure favorites table exists (no-op if already there)
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS favorites (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, product_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_user_product (user_id, product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$stmt = $conn->prepare("SELECT p.* , f.created_at AS favorited_at FROM favorites f JOIN products p ON p.id = f.product_id WHERE f.user_id = ? ORDER BY f.created_at DESC");
$stmt->bind_param('i', $uid);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>My Favorites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container favorites-page" style="margin-top:28px;">
    <div class="glass-card" style="padding:18px;">
        <h2>My Favorites</h2>
        <p class="muted">Products you have added to favorites.</p>

        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:12px; margin-top:12px;">
            <?php while($p = mysqli_fetch_assoc($res)): ?>
                <div class="product-card">
                    <a href="product.php?id=<?php echo intval($p['id']); ?>">
                        <img src="images/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                    </a>
                    <div class="meta" style="padding:8px;">
                        <h4 style="margin:0 0 6px;"><a href="product.php?id=<?php echo intval($p['id']); ?>"><?php echo htmlspecialchars($p['name']); ?></a></h4>
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                            <div class="price">PKR <?php echo number_format(floatval($p['price']),2); ?></div>
                            <form method="POST" action="favorite_action.php">
                                <input type="hidden" name="product_id" value="<?php echo intval($p['id']); ?>">
                                <button class="action-btn btn-deactivate" type="submit">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

    </div>
</div>

</body>
</html>

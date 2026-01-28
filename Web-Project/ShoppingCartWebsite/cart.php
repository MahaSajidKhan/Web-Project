<?php
session_start();

require_once __DIR__ . '/db.php';

function fmt_currency($amount){
    return number_format((float)$amount, 0, '.', ',');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="cart-page">
    <div class="header-bar">Your Cart</div>

    <?php if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])): ?>
        <div class="glass-card empty-cart">
            <h3>Your cart is empty</h3>
            <p class="muted">Browse products and add items to see them here.</p>
            <div class="empty-actions">
                <a href="index.php" class="btn-primary-custom">Continue Shopping</a>
            </div>
        </div>
    <?php else: ?>
        <?php
            $grand_total = 0;
            foreach ($_SESSION['cart'] as $id => $item) {
                $grand_total += ((float)$item['price'] * (int)$item['quantity']);
            }
        ?>
        <div class="cart-layout">
            <div class="cart-items">
                <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                    <?php
                        $name = htmlspecialchars($item['name']);
                        $price = (float)$item['price'];
                        $qty = (int)$item['quantity'];
                        $line_total = $price * $qty;
                        // fetch product image (if available)
                        $imgPath = '';
                        if(isset($conn)){
                            if($stmtI = $conn->prepare("SELECT image FROM products WHERE id = ? LIMIT 1")){
                                $pid = intval($id);
                                $stmtI->bind_param('i', $pid);
                                $stmtI->execute();
                                $resI = $stmtI->get_result();
                                if($rowI = $resI->fetch_assoc()){
                                    $imgPath = isset($rowI['image']) ? trim($rowI['image']) : '';
                                }
                                $stmtI->close();
                            }
                        }
                    ?>
                    <div class="cart-item glass-card">
                        <div class="ci-main">
                            <?php if(!empty($imgPath)): ?>
                            <a class="ci-thumb" href="product.php?id=<?php echo $id; ?>">
                                <img src="images/<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo $name; ?>">
                            </a>
                            <?php else: ?>
                            <div class="ci-thumb placeholder" aria-hidden="true"></div>
                            <?php endif; ?>
                            <div class="ci-info">
                                <a href="product.php?id=<?php echo $id; ?>" class="ci-name"><?php echo $name; ?></a>
                                <div class="ci-price">PKR <?php echo fmt_currency($price); ?></div>
                            </div>
                            <div class="ci-qty">
                                <form method="POST" action="update_cart.php" class="qty-form">
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                                    <input type="hidden" name="action" value="dec">
                                    <button type="submit" class="btn-qty" title="Decrease">−</button>
                                </form>
                                <div class="qty-count" aria-live="polite"><?php echo $qty; ?></div>
                                <form method="POST" action="update_cart.php" class="qty-form">
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                                    <input type="hidden" name="action" value="inc">
                                    <button type="submit" class="btn-qty" title="Increase">+</button>
                                </form>
                            </div>
                            <div class="ci-total">PKR <?php echo fmt_currency($line_total); ?></div>
                        </div>
                        <div class="ci-actions">
                            <a href="remove_from_cart.php?id=<?php echo $id; ?>" class="btn-primary-custom">Remove</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <aside class="cart-summary glass-card">
                <h3>Order Summary</h3>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <strong>PKR <?php echo fmt_currency($grand_total); ?></strong>
                </div>
                <div class="summary-row muted">
                    <span>Shipping</span>
                    <span>Calculated at checkout</span>
                </div>
                <div class="summary-row muted">
                    <span>Taxes</span>
                    <span>Calculated at checkout</span>
                </div>
                <div class="summary-divider"></div>
                <div class="summary-row total">
                    <span>Total</span>
                    <strong>PKR <?php echo fmt_currency($grand_total); ?></strong>
                </div>
                <div class="summary-actions">
                    <a href="checkout.php" class="btn-primary-custom">Proceed to Checkout</a>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

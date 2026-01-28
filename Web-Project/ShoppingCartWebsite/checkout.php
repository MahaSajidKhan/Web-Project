<?php
session_start();
include "db.php"; 
include "navbar.php";

if (!isset($_SESSION['user_id'])) {
    echo "<p>Please login to place order.</p>";
    echo "<a href='user_login.php'>Login Here</a>";
    exit;
}

$user_id = $_SESSION['user_id']; 
$cart = $_SESSION['cart'] ?? [];

if(empty($cart)){
    echo "<p>Your cart is empty.</p><a href='index.php'>Back to Home</a>";
    exit;
}

// preserve submitted values so we can re-fill on error/success
$old = ['name'=>'','email'=>'','phone'=>'','address'=>'','payment_method'=>'card'];
$cart_display = $cart; // snapshot for rendering summary even after placing order
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $old['name'] = trim($_POST['name'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $old['phone'] = trim($_POST['phone'] ?? '');
    $old['address'] = trim($_POST['address'] ?? '');
    $old['payment_method'] = trim($_POST['payment_method'] ?? 'card');

    $name = mysqli_real_escape_string($conn, $old['name']);
    $email = mysqli_real_escape_string($conn, $old['email']);
    $phone = mysqli_real_escape_string($conn, $old['phone']);
    $address = mysqli_real_escape_string($conn, $old['address']);

    $total_price = 0;
    foreach($cart as $item){
        $total_price += $item['price'] * $item['quantity'];
    }

    // Handle payment method and COD surcharge
    $payment_method = ($old['payment_method'] === 'cod') ? 'cod' : 'card';
    $cod_surcharge = 0;
    if($payment_method === 'cod'){
        $cod_surcharge = 250; // Rs.250 extra for Cash on Delivery
        $total_price += $cod_surcharge;
    }

    // Ensure orders table has 'status' and 'payment_method' columns
    $colCheck = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'status'");
    if(!$colCheck || mysqli_num_rows($colCheck) == 0){
        mysqli_query($conn, "ALTER TABLE orders ADD COLUMN status VARCHAR(32) DEFAULT 'pending'");
    }
    $colCheck2 = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'payment_method'");
    if(!$colCheck2 || mysqli_num_rows($colCheck2) == 0){
        mysqli_query($conn, "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(32) DEFAULT 'card'");
    }

    $sql = "INSERT INTO orders (user_id, name, email, phone, address, total_price, payment_method, status) 
            VALUES ('$user_id', '$name', '$email', '$phone', '$address', '$total_price', '$payment_method', 'pending')";

    if(mysqli_query($conn, $sql)){
        $order_id = mysqli_insert_id($conn);

        // Insert order items
        foreach($cart as $product_id => $item){
            $pid = intval($product_id);
            $qty = intval($item['quantity']);
            $price = floatval($item['price']);
            mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, quantity, price) 
                                 VALUES ('$order_id','$pid','$qty','$price')");
        }

        unset($_SESSION['cart']);
        $success = "Order placed successfully! Your Order ID: $order_id";
    } else {
        $error = "Error placing order: " . mysqli_error($conn);
    }
}
?>

<div class="container checkout-page">
    <div class="glass-card checkout-card">
        <h2 class="checkout-title">Checkout</h2>

        <?php if(isset($success)){ ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                &nbsp;<a href="track_order.php?id=<?php echo isset($order_id)?intval($order_id):''; ?>" style="margin-left:8px; font-weight:800;">Track order</a>
                <a href="index.php" style="margin-left:8px; font-weight:800;">Continue shopping</a>
            </div>
        <?php } ?>

        <?php if(isset($error)){ ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php } ?>

        <?php if(!isset($success)): ?>
        <form method="POST" class="admin-form">
            <div class="field">
                <label>Name</label>
                <input type="text" name="name" required value="<?php echo htmlspecialchars($old['name']); ?>">
            </div>

            <div class="field">
                <label>Email</label>
                <input type="email" name="email" required value="<?php echo htmlspecialchars($old['email']); ?>">
            </div>

            <div class="field">
                <label>Phone</label>
                <input type="text" name="phone" required value="<?php echo htmlspecialchars($old['phone']); ?>">
            </div>

            <div class="field">
                <label>Address</label>
                <textarea name="address" required><?php echo htmlspecialchars($old['address']); ?></textarea>
            </div>

            <div class="field">
                <label>Payment Method</label>
                <div style="display:flex; gap:12px; align-items:center; margin-top:6px">
                    <label style="display:flex; gap:6px; align-items:center"><input type="radio" name="payment_method" value="card" <?php echo ($old['payment_method']==='card')? 'checked':''; ?>> Card</label>
                    <label style="display:flex; gap:6px; align-items:center"><input type="radio" name="payment_method" value="cod" <?php echo ($old['payment_method']==='cod')? 'checked':''; ?>> Cash on Delivery</label>
                </div>
                <div style="font-size:13px; color:var(--muted); margin-top:6px">Note: Cash on Delivery will add Rs.250 surcharge.</div>
            </div>

            <div style="margin-top:10px;">
                <button type="submit" class="btn-primary-custom">Place Order</button>
            </div>
        </form>
        <?php endif; ?>

        <div class="related-panel checkout-related">
            <h4>Order Summary</h4>
            <?php if(empty($cart_display)){ ?>
                <div class="muted">Cart is empty.</div>
            <?php } else { ?>
                <div class="order-list">
                    <?php $subtotal = 0; foreach($cart_display as $pid => $item){ $line = $item['price'] * $item['quantity']; $subtotal += $line; ?>
                        <div class="order-line">
                            <div class="order-meta">
                                <div class="order-name"><?php echo htmlspecialchars($item['name'] ?? 'Product'); ?></div>
                                <div class="order-qty">Qty: <?php echo intval($item['quantity']); ?></div>
                            </div>
                            <div class="order-price"><?php echo number_format($line,2); ?></div>
                        </div>
                    <?php } ?>

                    <?php
                        // Payment selection snapshot for display
                        $selected_payment = $old['payment_method'] ?? 'card';
                        $cod_fee_display = ($selected_payment === 'cod') ? 250 : 0;
                        $total_display = $subtotal + $cod_fee_display;
                    ?>

                    <div class="order-divider"></div>
                    <div class="order-summary-row">
                        <div class="order-summary-label">Subtotal</div>
                        <div class="order-summary-value" id="summary-subtotal">PKR <?php echo number_format($subtotal,2); ?></div>
                    </div>

                    <div class="order-summary-row" id="cod-row" style="display:<?php echo ($cod_fee_display>0)?'flex':'none'; ?>; justify-content:space-between;">
                        <div class="order-summary-label">Cash on Delivery surcharge</div>
                        <div class="order-summary-value" id="summary-cod">PKR <?php echo number_format($cod_fee_display,2); ?></div>
                    </div>

                    <div class="order-summary-row muted-row">
                        <div>Shipping</div>
                        <div>Calculated at checkout</div>
                    </div>

                    <div class="order-summary-row" style="font-weight:700; margin-top:8px;">
                        <div class="order-summary-label">Total</div>
                        <div class="order-summary-value" id="summary-total">PKR <?php echo number_format($total_display,2); ?></div>
                    </div>
                </div>
                <?php if(!isset($success)): ?>
                <div style="margin-top:12px;">
                    <a href="cart.php" class="btn-primary-custom edit-cart">Edit Cart</a>
                </div>
                <?php endif; ?>
            <?php } ?>
        </div>
    </div>
</div>
</div>

<script>
// Update order summary when payment method changes
(function(){
    const radios = document.querySelectorAll('input[name="payment_method"]');
    const codRow = document.getElementById('cod-row');
    const summaryCod = document.getElementById('summary-cod');
    const summaryTotal = document.getElementById('summary-total');
    const summarySubtotal = document.getElementById('summary-subtotal');
    const COD_FEE = 250;

    if(!summaryTotal || !summarySubtotal) return;

    // parse currency text like "PKR 1,234.56" -> number
    function parseCurrency(str){
        if(!str) return 0;
        // remove everything except digits, dot and minus
        const cleaned = str.replace(/[^0-9.\-]+/g, '');
        return parseFloat(cleaned) || 0;
    }

    // format number as PKR with 2 decimals and thousands separators
    function formatPKR(n){
        return 'PKR ' + n.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
    }

    function update(){
        // Only run live updates when payment radios are present (i.e. when form is visible)
        if(!radios || radios.length === 0) return;
        let selected = 'card';
        radios.forEach(r=>{ if(r.checked) selected = r.value; });
        const subtotal = parseCurrency(summarySubtotal.textContent);
        if(selected === 'cod'){
            if(codRow) codRow.style.display = 'flex';
            if(summaryCod) summaryCod.textContent = formatPKR(COD_FEE);
            if(summaryTotal) summaryTotal.textContent = formatPKR(subtotal + COD_FEE);
        } else {
            if(codRow) codRow.style.display = 'none';
            if(summaryCod) summaryCod.textContent = formatPKR(0);
            if(summaryTotal) summaryTotal.textContent = formatPKR(subtotal);
        }
    }

    // Attach listeners only if radios exist (form visible). Don't run initial update if form is hidden (order placed), to preserve server-rendered summary.
    if(radios && radios.length > 0){
        radios.forEach(r => r.addEventListener('change', update));
        // initial update
        update();
    }
})();
</script>

<?php
include __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Terms & Conditions</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>

<div class="container" style="margin-top:28px;">
  <div class="glass-card">
    <h1>Terms &amp; Conditions</h1>
    <p class="muted">Last updated: <?php echo date('F j, Y'); ?></p>

    <section style="margin-top:18px;">
      <h3>Acceptance of Terms</h3>
      <p>By using this website you agree to these terms. Please do not use the site if you disagree.</p>
    </section>

    <section style="margin-top:12px;">
      <h3>Orders and Payments</h3>
      <p>All orders are subject to availability and confirmation of the order price. We accept payment methods listed on checkout.</p>
    </section>

    <section style="margin-top:12px;">
      <h3>Limitation of Liability</h3>
      <p>To the extent permitted by law, the site will not be liable for any indirect or consequential loss arising from the use of the site.</p>
    </section>

    <p style="margin-top:16px;" class="muted">This terms page is a starter template. For production deployments, please get appropriate legal advice.</p>

  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
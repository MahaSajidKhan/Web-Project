<?php
include __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Privacy Policy</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>

<div class="container" style="margin-top:28px;">
  <div class="glass-card">
    <h1>Privacy Policy</h1>
    <p class="muted">Last updated: <?php echo date('F j, Y'); ?></p>

    <section style="margin-top:18px;">
      <h3>Introduction</h3>
      <p>We respect your privacy and are committed to protecting your personal data. This page explains what data we collect, why we collect it, and how you can control it.</p>
    </section>

    <section style="margin-top:12px;">
      <h3>Data We Collect</h3>
      <ul>
        <li>Account information (name, email) when you register.</li>
        <li>Order and payment information when you place an order.</li>
        <li>Usage data (pages visited, product views) to improve our service.</li>
      </ul>
    </section>

    <section style="margin-top:12px;">
      <h3>How We Use Data</h3>
      <p>We use data to process orders, communicate with you, improve the website, and for fraud prevention. We do not sell your personal data.</p>
    </section>

    <section style="margin-top:12px;">
      <h3>Your Choices</h3>
      <p>You can manage account information from your account page and contact us at the support email listed in the footer.</p>
    </section>

    <p style="margin-top:16px;" class="muted">This is a minimal privacy policy for the demo site. For production use, consult a legal professional and adapt to local laws (GDPR, PDPA, etc.).</p>

  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
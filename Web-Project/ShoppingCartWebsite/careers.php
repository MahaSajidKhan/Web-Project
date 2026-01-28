<?php
include __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Careers</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>

<div class="container" style="margin-top:28px;">
  <div class="glass-card">
    <h1>Careers</h1>
    <p class="muted">We occasionally hire passionate people. Check this page for openings or contact us at the support email in the footer.</p>

    <section style="margin-top:18px;">
      <h3>Open Positions</h3>
      <ul>
        <li>No open positions at the moment — please check back later.</li>
      </ul>
    </section>

    <section style="margin-top:12px;">
      <h3>How to apply</h3>
      <p>Please send your CV and a short note to <a href="mailto:support@example.com">support@example.com</a>. For production sites, replace this email with your HR contact.</p>
    </section>

  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Get and sanitize product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($product_id <= 0){
  header('Location: index.php');
  exit;
}

// Ensure products.views column exists (non-destructive)
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'views'");
if(!$colCheck || mysqli_num_rows($colCheck) == 0){
  @mysqli_query($conn, "ALTER TABLE products ADD COLUMN views INT NOT NULL DEFAULT 0 AFTER image");
}

// Increment the view counter safely (use prepared statement)
if($stmtUp = $conn->prepare("UPDATE products SET views = views + 1 WHERE id = ?")){
  $stmtUp->bind_param('i', $product_id);
  $stmtUp->execute();
  $stmtUp->close();
} else {
  // fallback
  mysqli_query($conn, "UPDATE products SET views = views + 1 WHERE id = " . $product_id);
}

// Fetch product details after increment
if($stmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?")){
  $stmt->bind_param('i', $product_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $product = $res->fetch_assoc();
  $stmt->close();
} else {
  // last resort
  $product_id_esc = intval($product_id);
  $run = mysqli_query($conn, "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = $product_id_esc");
  $product = mysqli_fetch_assoc($run);
}

// check if current user has favorited this product
$isFav = false;
if(isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0){
  $uid = intval($_SESSION['user_id']);
  // ensure favorites table exists (non-destructive)
  $tbl = mysqli_query($conn, "SHOW TABLES LIKE 'favorites'");
  if(!$tbl || mysqli_num_rows($tbl) == 0){
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS favorites (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, product_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_user_product (user_id, product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  }
  $stmtf = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ? LIMIT 1");
  $stmtf->bind_param('ii', $uid, $product_id);
  $stmtf->execute();
  $rf = $stmtf->get_result()->fetch_assoc();
  $stmtf->close();
  if($rf) $isFav = true;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $product['name']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="header-bar"><?php echo $product['name']; ?></div>

<div class="container">
  <div class="product-detail-layout">
    <div class="glass-card product-card">
      <img src="images/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
      <div class="meta">
        <h2><?php echo $product['name']; ?></h2>
        <p><strong>Category:</strong> <?php echo $product['category_name']; ?></p>
        <p><strong>Price:</strong> <span class="price"><?php echo $product['price']; ?> PKR</span></p>
        <p><strong>Description:</strong><br><?php echo $product['description']; ?></p>
        <?php if(isset($_SESSION['user_id']) && !isset($_SESSION['admin'])): ?>
            <form method="POST" action="favorite_action.php" style="display:inline-block; margin-right:8px;">
                <input type="hidden" name="product_id" value="<?php echo intval($product['id']); ?>">
                <button type="submit" class="fav-btn <?php echo $isFav ? 'favorited' : ''; ?>" aria-pressed="<?php echo $isFav ? 'true' : 'false'; ?>" title="<?php echo $isFav ? 'Remove from favorites' : 'Add to favorites'; ?>">
                    <?php if($isFav): ?>
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 21s-7-4.35-9.5-7.09C-1 11.5 2 7 6.5 7 8.4 7 9.9 8 12 10c2.1-2 3.6-3 5.5-3C20 7 23 10 23 13.91 20.5 16.65 12 21 12 21z" fill="currentColor"/></svg>
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 21s-7-4.35-9.5-7.09C-1 11.5 2 7 6.5 7 8.4 7 9.9 8 12 10c2.1-2 3.6-3 5.5-3C20 7 23 10 23 13.91 20.5 16.65 12 21 12 21z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <?php endif; ?>
                </button>
            </form>
        <?php else: ?>
          <?php if(!isset($_SESSION['admin'])): ?>
            <a class="btn-primary-custom" href="user_login.php" style="margin-right:8px;">Add to Favorites</a>
          <?php endif; ?>
        <?php endif; ?>
        <?php if(!isset($_SESSION['admin'])): ?>
          <a class="btn-primary-custom" href="add_to_cart.php?id=<?php echo $product['id']; ?>">Add to Cart</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- You May Also Like (global recommendations) -->
    <aside class="related-panel">
      <h4>You May Also Like</h4>
          <div class="rec-grid tilt" style="padding-top:6px;">
          <?php
          // Show many products site-wide (exclude current) — cap to 100 for performance
          $recommended = [];
          $stmtRec = $conn->prepare("SELECT id, name, image, price, views FROM products WHERE id != ? ORDER BY views DESC LIMIT 100");
          // If views column doesn't exist, fallback to newest first
          $colViews = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'views'");
          if(!$colViews || mysqli_num_rows($colViews) == 0){
            $stmtRec = $conn->prepare("SELECT id, name, image, price, id AS views FROM products WHERE id != ? ORDER BY id DESC LIMIT 100");
          }
          $stmtRec->bind_param('i', $product_id);
          $stmtRec->execute();
          $resRec = $stmtRec->get_result();
          while($r = $resRec->fetch_assoc()) $recommended[] = $r;
          $stmtRec->close();

          if(empty($recommended)){
            echo '<div class="muted">No recommendations available.</div>';
          } else {
            foreach($recommended as $rp){
              // popular badge if views high
              $badge = (isset($rp['views']) && intval($rp['views']) > 50) ? '<div class="badge">Popular</div>' : '';
              echo '<div class="rec-card">';
              echo $badge;
              echo '<a href="product.php?id=' . intval($rp['id']) . '"><img src="images/' . htmlspecialchars($rp['image']) . '" alt="' . htmlspecialchars($rp['name']) . '"></a>';
              echo '<div class="rec-meta"><div class="title">' . htmlspecialchars($rp['name']) . '</div><div class="price">PKR ' . number_format(floatval($rp['price']),2) . '</div></div>';
              echo '<div class="rec-actions">';
              echo '<a href="product.php?id=' . intval($rp['id']) . '" class="btn-primary-custom" style="padding:8px 10px;">View</a>';
              echo '</div>';
              echo '</div>';
            }
          }
          ?>
          </div>
    </aside>

  </div>

  <!-- Same-category slider placed below the main product card -->
  <div class="samecat-wrap container">
    <div class="samecat-header">
      <h3 style="margin:0">More From This Category</h3>
    </div>
    <div class="samecat-slider">
      <div id="samecat-track-<?php echo $product_id; ?>" class="samecat-track">
        <?php
        // fetch same-category products for slider
        $sameCat = [];
        if(!empty($product['category_id'])){
            $cid = intval($product['category_id']);
            $stmtC = $conn->prepare("SELECT id, name, image, price FROM products WHERE category_id = ? AND id != ? ORDER BY id DESC LIMIT 12");
            $stmtC->bind_param('ii', $cid, $product_id);
            $stmtC->execute();
            $resC = $stmtC->get_result();
            while($r = $resC->fetch_assoc()) $sameCat[] = $r;
            $stmtC->close();
        }

        if(empty($sameCat)){
            echo '<div class="muted">No other items in this category.</div>';
        } else {
            foreach($sameCat as $rp){
                echo '<div class="slide-item">';
                echo '<a href="product.php?id=' . intval($rp['id']) . '"><img src="images/' . htmlspecialchars($rp['image']) . '" alt="' . htmlspecialchars($rp['name']) . '"></a>';
                echo '<div class="meta"><div class="name">' . htmlspecialchars($rp['name']) . '</div><div class="price">PKR ' . number_format(floatval($rp['price']),2) . '</div></div>';
                echo '</div>';
            }
        }
        ?>
      </div>
    </div>
  </div>
  </div>
</div>

</body>
</html>
<script>
// Simple slider controls for same-category track
document.addEventListener('DOMContentLoaded', function(){
  // (arrow controls removed) — sliders remain controllable via wheel/keyboard/drag

  // make tracks respond to wheel when hovered (horizontal scroll)
  function enableWheelScroll(track){
    track.addEventListener('wheel', function(e){
      if(Math.abs(e.deltaY) > Math.abs(e.deltaX)){
        e.preventDefault();
        track.scrollBy({ left: e.deltaY * 1.5 });
      }
    }, { passive: false });
  }
  document.querySelectorAll('.samecat-track, .rec-track').forEach(function(track){ enableWheelScroll(track); });
  // for vertical rec-grid we don't change wheel behavior; attach tilt to vertical grid as well
  // attach tilt to rec-grid cards
  attachTilt('.rec-grid', '.rec-card');

  // keyboard nav (left/right) for focused tracks
  document.addEventListener('keydown', function(e){
    if(document.activeElement && (document.activeElement.classList.contains('samecat-track') || document.activeElement.classList.contains('rec-track'))){
      var track = document.activeElement;
      if(e.key === 'ArrowLeft'){ track.scrollBy({ left: -220, behavior: 'smooth' }); }
      if(e.key === 'ArrowRight'){ track.scrollBy({ left: 220, behavior: 'smooth' }); }
    }
  });
  // hover tilt effect for cards
  function attachTilt(containerSelector, itemSelector){
    document.querySelectorAll(containerSelector).forEach(function(container){
      container.querySelectorAll(itemSelector).forEach(function(item){
        item.addEventListener('mousemove', function(ev){
          var rect = item.getBoundingClientRect();
          var cx = rect.left + rect.width/2;
          var cy = rect.top + rect.height/2;
          var dx = (ev.clientX - cx) / rect.width;
          var dy = (ev.clientY - cy) / rect.height;
          var rx = (-dy * 6).toFixed(2);
          var ry = (dx * 8).toFixed(2);
          item.style.transform = 'translateZ(8px) rotateX(' + rx + 'deg) rotateY(' + ry + 'deg)';
        });
        item.addEventListener('mouseleave', function(){ item.style.transform = ''; });
      });
    });
  }
  attachTilt('.samecat-track', '.slide-item');
  attachTilt('.rec-track', '.rec-card');
  attachTilt('.rec-grid', '.rec-card');

  // intersection observer to animate elements into view
  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(en){ if(en.isIntersecting) en.target.classList.add('in-view'); });
  }, { threshold: 0.15 });
  document.querySelectorAll('.rec-card, .slide-item').forEach(function(el){ io.observe(el); });

});
</script>

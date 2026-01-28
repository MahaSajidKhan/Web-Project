<?php 
include "db.php";

// helper: resolve image filename to an existing relative path (images/ or category_images/),
// handles trimming, case-insensitive matches, and full URLs.
function resolveImagePath($name){
    $name = trim((string)$name);
    if(empty($name)) return false;
    // if it's already an absolute URL, return as-is
    if(preg_match('#^https?://#i', $name)) return $name;

    $candidates = [__DIR__ . '/images/' . $name, __DIR__ . '/category_images/' . $name];
    foreach($candidates as $p){ if(file_exists($p)) return str_replace('\\','/', substr($p, strlen(__DIR__) + 1)); }

    // try case-insensitive match in images/ and category_images/
    $dirs = [__DIR__ . '/images/', __DIR__ . '/category_images/'];
    foreach($dirs as $d){
        if(!is_dir($d)) continue;
        $files = scandir($d);
        foreach($files as $f){ if(strtolower($f) === strtolower($name) && is_file($d . $f)) return str_replace('\\','/', substr($d . $f, strlen(__DIR__) + 1)); }
    }

    return false;
}

// Pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$total_query = "SELECT COUNT(*) AS total FROM categories";
$total_result = mysqli_query($conn, $total_query);
$total_categories = mysqli_fetch_assoc($total_result)['total'];

$query = "SELECT * FROM categories LIMIT $start, $limit";
$run = mysqli_query($conn, $query);

$total_pages = ceil($total_categories / $limit);
?>

<!DOCTYPE html>
<html>
<head>
<title>Mahazon - Home</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/style.css">

</head>

<body>
<?php include "navbar.php"; ?>

<!-- ambient lightning overlay -->
<div class="lightning-overlay"><div class="flash"></div></div>

<!-- 3D TWIST CAROUSEL -->
<!-- HERO SEARCH removed (search is now in navbar) -->

<!-- 3D TWIST CAROUSEL -->
<div class="carousel-3d-container">
  <div class="carousel-3d" id="carousel3d">
    <div class="carousel-slide"><img src="images/cosmetics_lipstick.jpg" alt="Slide 1"></div>
    <div class="carousel-slide"><img src="images/gaming_ps5.jpg" alt="Slide 2"></div>
    <div class="carousel-slide"><img src="images/watch_casio.jpg" alt="Slide 3"></div>
  </div>
</div>

<!-- categories moved: rendered after Featured Items below -->
    <!-- carousel controls (inside container so positioning matches) -->
    <div class="carousel-controls" aria-hidden="false">
        <button id="prevBtn" class="carousel-control left" aria-label="Previous slide">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <button id="nextBtn" class="carousel-control right" aria-label="Next slide">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </div>

<!-- HEADER -->
<div class="header-bar reveal fade-up">
    Mahazon
</div>

<!-- (search moved above carousel) -->

<!-- FEATURED ITEMS (Most Viewed & Best Sellers) -->
<?php
// Ensure products.views column exists for tracking views
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'views'");
if(!$colCheck || mysqli_num_rows($colCheck) == 0){
        @mysqli_query($conn, "ALTER TABLE products ADD COLUMN views INT NOT NULL DEFAULT 0 AFTER image");
}

// Most viewed products
$most_viewed_q = "SELECT id, name, price, image, description, views FROM products ORDER BY views DESC LIMIT 6";
$most_viewed_res = mysqli_query($conn, $most_viewed_q);

// Best sellers (aggregate order_items)
$best_sold_q = "SELECT p.id, p.name, p.price, p.image, p.description, IFNULL(SUM(oi.quantity),0) AS sold_qty
                                FROM products p
                                LEFT JOIN order_items oi ON oi.product_id = p.id
                                GROUP BY p.id
                                ORDER BY sold_qty DESC
                                LIMIT 6";
$best_sold_res = mysqli_query($conn, $best_sold_q);
?>

<div class="container">
    <h3 class="fw-bold mb-4">Featured Items</h3>

    <div class="row">
        <div class="col-12 mb-4 reveal fade-up">
            <div class="glass-card" style="padding:18px;">
                <h4>Most Viewed</h4>
                <div class="row mt-3">
                    <?php if($most_viewed_res && mysqli_num_rows($most_viewed_res) > 0): ?>
                        <?php while($p = mysqli_fetch_assoc($most_viewed_res)): ?>
                            <div class="col-12 mb-3">
                                <div class="product-card glass-card" style="display:flex; gap:12px; align-items:center; padding:10px;">
                                    <?php $imgPath = resolveImagePath($p['image']); if($imgPath): ?>
                                                        <a href="product.php?id=<?php echo $p['id']; ?>" title="View <?php echo htmlspecialchars($p['name']); ?>">
                                                            <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" style="width:96px; height:72px; object-fit:contain; border-radius:8px; background:rgba(255,255,255,0.02);">
                                                        </a>
                                                    <?php else: ?>
                                        <div style="width:96px; height:72px; border-radius:8px; background:linear-gradient(90deg, rgba(126,231,197,0.06), rgba(74,217,166,0.02)); display:flex; align-items:center; justify-content:center; color:var(--muted);">No Image</div>
                                    <?php endif; ?>
                                    <div style="flex:1;">
                                        <a href="product.php?id=<?php echo $p['id']; ?>" style="font-weight:700; color:#eafaf1; text-decoration:none"><?php echo htmlspecialchars($p['name']); ?></a>
                                        <div style="color:var(--muted); font-size:13px; margin-top:6px">PKR <?php echo number_format($p['price'],2); ?> • Views: <?php echo intval($p['views']); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="muted">No viewed products yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 mb-4 reveal fade-up">
            <div class="glass-card" style="padding:18px;">
                <h4>Best Sellers</h4>
                <div class="row mt-3">
                    <?php if($best_sold_res && mysqli_num_rows($best_sold_res) > 0): ?>
                        <?php while($p = mysqli_fetch_assoc($best_sold_res)): ?>
                            <div class="col-12 mb-3">
                                <div class="product-card glass-card" style="display:flex; gap:12px; align-items:center; padding:10px;">
                                    <?php $imgPath = resolveImagePath($p['image']); if($imgPath): ?>
                                        <a href="product.php?id=<?php echo $p['id']; ?>" title="View <?php echo htmlspecialchars($p['name']); ?>">
                                            <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" style="width:96px; height:72px; object-fit:contain; border-radius:8px; background:rgba(255,255,255,0.02);">
                                        </a>
                                    <?php else: ?>
                                        <div style="width:96px; height:72px; border-radius:8px; background:linear-gradient(90deg, rgba(126,231,197,0.06), rgba(74,217,166,0.02)); display:flex; align-items:center; justify-content:center; color:var(--muted);">No Image</div>
                                    <?php endif; ?>
                                    <div style="flex:1;">
                                        <a href="product.php?id=<?php echo $p['id']; ?>" style="font-weight:700; color:#eafaf1; text-decoration:none"><?php echo htmlspecialchars($p['name']); ?></a>
                                        <div style="color:var(--muted); font-size:13px; margin-top:6px">PKR <?php echo number_format($p['price'],2); ?> • Sold: <?php echo intval($p['sold_qty']); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="muted">No sales data yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category cards (rendered after Featured Items) -->

<section class="container mt-36">
    <h3 class="section-title">Explore Categories</h3>
    <div class="category-grid">
        <?php
        // Render categories as cards with product counts; detect optional columns
        $hasDesc = false;
        $colCheck = $conn->query("SHOW COLUMNS FROM categories LIKE 'description'");
        if($colCheck && $colCheck->num_rows > 0) $hasDesc = true;

        $hasImage = false;
        $colCheckImg = $conn->query("SHOW COLUMNS FROM categories LIKE 'category_image'");
        if($colCheckImg && $colCheckImg->num_rows > 0) $hasImage = true;

        // Build select list dynamically based on available columns
        $selectCols = "c.id, c.name";
        if($hasDesc) $selectCols .= ", c.description";
        if($hasImage) $selectCols .= ", c.category_image";
        $selectCols .= ", COUNT(p.id) AS product_count";

        $sql = "SELECT $selectCols
                  FROM categories c
                  LEFT JOIN products p ON p.category_id = c.id
                  GROUP BY c.id
                  ORDER BY c.name ASC";

        $catStmt = $conn->prepare($sql);
        if($catStmt && $catStmt->execute()){
            $res = $catStmt->get_result();
            while($c = $res->fetch_assoc()){
                $catLink = 'category.php?id=' . urlencode($c['id']);
                $catPictureName = ($hasImage && isset($c['category_image'])) ? $c['category_image'] : '';
                $desc = ($hasDesc && !empty($c['description'])) ? htmlspecialchars($c['description']) : '';
                $count = isset($c['product_count']) ? intval($c['product_count']) : 0;
                echo "<div class=\"category-card\">";
                echo "<a href=\"$catLink\">";
                echo "<div class=\"cat-title\">" . htmlspecialchars($c['name']) . "</div>";
                echo "<div class=\"cat-image-container\">";
                if(!empty($catPictureName) && file_exists(__DIR__ . '/category_images/' . $catPictureName)){
                    echo "<img src=\"category_images/" . htmlspecialchars($catPictureName) . "\" alt=\"" . htmlspecialchars($c['name']) . "\" style=\"width:200px; height:130px; object-fit:contain; border-radius:8px; background:rgba(255,255,255,0.02);\">";
                } else {
                    echo "<div style=\"width:200px; height:130px; border-radius:8px; background:linear-gradient(90deg, rgba(126,231,197,0.06), rgba(74,217,166,0.02)); display:flex; align-items:center; justify-content:center; color:var(--muted);\">No Image</div>";
                }
                echo "</div>";
                echo "<div class=\"cat-meta\">";
                if(!empty($desc)) echo "<div class=\"cat-sub\">$desc</div>";
                echo "<div class=\"cat-count\">" . $count . "" . ($count==1 ? ' item' : ' items') . "</div>";
                echo "</div>"; // cat-meta
                echo "</a></div>";
            }
            $catStmt->close();
        } else {
            // Fallback: small friendly message if query fails
            echo '<div class="muted">No categories available.</div>';
        }
        ?>
    </div>
</section>

<!-- JUST FOR YOU: personalized recommendations based on favorites and last search -->
<section class="container mt-24">
    <h3 class="section-title">Just For You</h3>
    <div class="category-grid">
        <?php
        // Personalized recommendations:
        // 1) If logged in, use favorite categories to surface products
        // 2) Also include matches from the user's last search (if available)
        // 3) Fallback to most viewed products

        $justRes = null;
        $candidates = [];

        if(session_status() === PHP_SESSION_NONE){ /* navbar.php usually starts session, but ensure here */ session_start(); }

        // helper: safe escape
        function escq($conn, $s){ return mysqli_real_escape_string($conn, $s); }

        if(isset($_SESSION['user_id']) && $_SESSION['user_id']){
            $uid = intval($_SESSION['user_id']);
            // top categories from favorites
            $catQ = mysqli_query($conn, "SELECT p.category_id, COUNT(*) AS ccount FROM favorites f JOIN products p ON p.id = f.product_id WHERE f.user_id = $uid AND p.category_id IS NOT NULL GROUP BY p.category_id ORDER BY ccount DESC LIMIT 3");
            $cats = [];
            if($catQ && mysqli_num_rows($catQ) > 0){
                while($r = mysqli_fetch_assoc($catQ)){
                    if(!empty($r['category_id'])) $cats[] = intval($r['category_id']);
                }
            }

            $conds = [];
            if(!empty($cats)){
                $conds[] = "p.category_id IN (" . implode(',', $cats) . ")";
            }

            if(!empty($_SESSION['last_search'])){
                $ls = escq($conn, $_SESSION['last_search']);
                $conds[] = "(p.name LIKE '%$ls%' OR p.description LIKE '%$ls%')";
            }

            if(!empty($conds)){
                $sql = "SELECT DISTINCT p.id, p.name, p.price, p.image, p.description, p.views FROM products p WHERE (" . implode(' OR ', $conds) . ") ORDER BY p.views DESC LIMIT 8";
                $justRes = mysqli_query($conn, $sql);
            }
        } elseif(!empty($_SESSION['last_search'])){
            // not logged in: use last search if available
            $ls = escq($conn, $_SESSION['last_search']);
            $sql = "SELECT p.id, p.name, p.price, p.image, p.description, p.views FROM products p WHERE p.name LIKE '%$ls%' OR p.description LIKE '%$ls%' ORDER BY p.views DESC LIMIT 8";
            $justRes = mysqli_query($conn, $sql);
        }

        // fallback to most viewed if nothing personalized
        if(!$justRes || ($justRes && mysqli_num_rows($justRes) == 0)){
            $justRes = mysqli_query($conn, "SELECT id, name, price, image, description, views FROM products ORDER BY views DESC LIMIT 8");
        }

        // Render results similar to other product lists
        if($justRes && mysqli_num_rows($justRes) > 0){
            while($p = mysqli_fetch_assoc($justRes)){
                echo '<div class="category-card">';
                $imgTag = '';
                $imgPath = resolveImagePath($p['image']);
                if($imgPath){
                    $imgTag = '<img src="' . htmlspecialchars($imgPath) . '" alt="' . htmlspecialchars($p['name']) . '" style="width:200px; height:130px; object-fit:contain; border-radius:8px; background:rgba(255,255,255,0.02);">';
                } else {
                    $imgTag = '<div style="width:200px; height:130px; border-radius:8px; background:linear-gradient(90deg, rgba(126,231,197,0.06), rgba(74,217,166,0.02)); display:flex; align-items:center; justify-content:center; color:var(--muted);">No Image</div>';
                }
                echo '<a href="product.php?id=' . urlencode($p['id']) . '">';
                echo '<div class="cat-image-container">' . $imgTag . '</div>';
                echo '<div class="cat-meta">';
                echo '<div class="cat-title">' . htmlspecialchars($p['name']) . '</div>';
                echo '<div class="cat-count">PKR ' . number_format($p['price'],2) . '</div>';
                echo '</div>';
                echo '</a></div>';
            }
        } else {
            echo '<div class="muted">No personalized recommendations yet.</div>';
        }
        ?>
    </div>
</section>

<?php include __DIR__ . '/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- SCROLL REVEAL SCRIPT -->
<script>
const reveals = document.querySelectorAll('.reveal');

const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if(entry.isIntersecting){
            entry.target.classList.add('active');
        }
    });
}, { threshold: 0.2 });

reveals.forEach(el => observer.observe(el));
</script>

<!-- 3D CAROUSEL SCRIPT -->
<script>
const carousel = document.getElementById('carousel3d');
const slides = document.querySelectorAll('.carousel-slide');
let index = 0;
const total = slides.length;

// Position slides in circle
const theta = 360 / total;
slides.forEach((slide, i) => {
  const angle = theta * i;
  slide.style.transform = `rotateY(${angle}deg) translateZ(400px)`;
});

// set initial carousel transform so the camera is pulled back
carousel.style.transform = `translateZ(-400px) rotateY(0deg)`;

// Auto rotate function
function rotateCarousel() {
  index++;
  const angle = theta * index * -1;
  carousel.style.transform = `translateZ(-400px) rotateY(${angle}deg)`;
}

// Allow manual control and reset of auto-rotate
let autoRotate = setInterval(rotateCarousel, 3000); // rotate every 3s

function goToIndex(newIndex){
    index = ((newIndex % total) + total) % total; // wrap
    const angle = theta * index * -1;
    carousel.style.transform = `translateZ(-400px) rotateY(${angle}deg)`;
}

function nextCarousel(){
    goToIndex(index + 1);
    clearInterval(autoRotate);
    autoRotate = setInterval(rotateCarousel, 3000);
}

function prevCarousel(){
    goToIndex(index - 1);
    clearInterval(autoRotate);
    autoRotate = setInterval(rotateCarousel, 3000);
}

// wire up buttons
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
if(prevBtn && nextBtn){
    prevBtn.addEventListener('click', prevCarousel);
    nextBtn.addEventListener('click', nextCarousel);
}

// keyboard support (left / right)
document.addEventListener('keydown', (e) => {
    if(e.key === 'ArrowLeft') prevCarousel();
    if(e.key === 'ArrowRight') nextCarousel();
});
</script>

</body>
</html>
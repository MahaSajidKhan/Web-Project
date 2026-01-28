<?php
session_start();

// If admin not logged in, redirect to login
if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<?php
require_once 'db.php';
// fetch some quick stats
$counts = [];
$q = mysqli_query($conn, "SELECT COUNT(*) as c FROM products"); $r = mysqli_fetch_assoc($q); $counts['products'] = $r['c'] ?? 0;
$q = mysqli_query($conn, "SELECT COUNT(*) as c FROM categories"); $r = mysqli_fetch_assoc($q); $counts['categories'] = $r['c'] ?? 0;
$q = mysqli_query($conn, "SELECT COUNT(*) as c FROM users"); $r = mysqli_fetch_assoc($q); $counts['users'] = $r['c'] ?? 0;
$q = mysqli_query($conn, "SELECT COUNT(*) as c FROM orders"); $r = mysqli_fetch_assoc($q); $counts['orders'] = $r['c'] ?? 0;
// open user issues (new)
$q = mysqli_query($conn, "SELECT COUNT(*) as c FROM issues WHERE status = 'open'"); $r = mysqli_fetch_assoc($q); $counts['issues'] = $r['c'] ?? 0;
// total revenue
$q = mysqli_query($conn, "SELECT IFNULL(SUM(total_price),0) as revenue FROM orders"); $r = mysqli_fetch_assoc($q); $counts['revenue'] = $r['revenue'] ?? 0.00;
// average order value
$counts['avg_order'] = ($counts['orders'] > 0) ? round(($counts['revenue'] / $counts['orders']), 2) : 0.00;

// ------------------
// Insights data for chart & sparkline (range selection)
// supported ranges: 7d, 30d, 6m, all
$range = isset($_GET['range']) ? $_GET['range'] : '6m';
if (!in_array($range, ['7d','30d','6m','all'])) $range = '6m';

if ($range === '7d') { $periodType = 'day'; $periodCount = 7; }
elseif ($range === '30d') { $periodType = 'day'; $periodCount = 30; }
elseif ($range === '6m') { $periodType = 'month'; $periodCount = 6; }
else { $periodType = 'month'; $periodCount = 12; }

$labels = [];
$revData = [];
$ordersData = [];

// build period labels (oldest -> newest)
if ($periodType === 'day') {
    // days
    for ($i = $periodCount - 1; $i >= 0; $i--) {
        $dt = date('Y-m-d', strtotime("-{$i} days"));
        $labels[] = $dt; // use Y-m-d as key
    }
    $startDate = $labels[0] . ' 00:00:00';
    $sql = "SELECT DATE(created_at) as period, IFNULL(SUM(total_price),0) as revenue, COUNT(*) as cnt FROM orders WHERE created_at >= '$startDate' GROUP BY period ORDER BY period ASC";
} else {
    // months
    for ($i = $periodCount - 1; $i >= 0; $i--) {
        $dt = date('Y-m', strtotime("-{$i} months"));
        $labels[] = $dt; // use YYYY-MM
    }
    $startDate = date('Y-m-01 00:00:00', strtotime("-".($periodCount-1)." months"));
    $sql = "SELECT DATE_FORMAT(created_at,'%Y-%m') as period, IFNULL(SUM(total_price),0) as revenue, COUNT(*) as cnt FROM orders WHERE created_at >= '$startDate' GROUP BY period ORDER BY period ASC";
}

$mapRev = [];
$mapCnt = [];
$res = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($res)){
    $mapRev[$row['period']] = floatval($row['revenue']);
    $mapCnt[$row['period']] = intval($row['cnt']);
}

foreach ($labels as $key) {
    $revData[] = isset($mapRev[$key]) ? $mapRev[$key] : 0;
    $ordersData[] = isset($mapCnt[$key]) ? $mapCnt[$key] : 0;
}

// compute revenue delta (last vs previous)
$lastRev = end($revData);
$prevRev = (count($revData) >= 2) ? $revData[count($revData)-2] : 0;
$revDelta = $lastRev - $prevRev;
$revDeltaPct = ($prevRev != 0) ? round(($revDelta / max(1,$prevRev)) * 100, 2) : null;
// pretty formats
$lastRevFmt = number_format($lastRev, 2);
$revDeltaFmt = number_format(abs($revDelta), 2);
// ------------------
?>

<main class="admin-dashboard container">
    <div class="header glass-card">
        <h1>Admin Dashboard</h1>
        <div class="admin-actions">
            <a class="btn-primary-custom" href="add_product.php">Add Product</a>
            <a class="btn-primary-custom" href="manage_products.php">Manage Products</a>
            <a class="btn-primary-custom" href="add_category.php">Add Category</a>
        </div>
    </div>

    <section class="admin-stats">
        <div class="stat-card glass-card">
            <div>
                <div class="label">Products</div>
                <div class="value"><span class="stat-value" data-value="<?php echo intval($counts['products']); ?>"><?php echo intval($counts['products']); ?></span></div>
            </div>
            <div class="icon">📦</div>
        </div>

        <a href="categories_list.php" style="text-decoration:none; color:inherit;">
        <div class="stat-card glass-card">
            <div>
                <div class="label">Categories</div>
                <div class="value"><?php echo intval($counts['categories']); ?></div>
            </div>
            <div class="icon">🗂️</div>
        </div>
        </a>

        <a href="manage_users.php" style="text-decoration:none; color:inherit;">
        <div class="stat-card glass-card">
            <div>
                <div class="label">Users</div>
                <div class="value"><?php echo intval($counts['users']); ?></div>
            </div>
            <div class="icon">👥</div>
        </div>
        </a>

        <a href="manage_orders.php" style="text-decoration:none; color:inherit;">
        <div class="stat-card glass-card">
            <div>
                <div class="label">Orders</div>
                <div class="value"><span class="stat-value" data-value="<?php echo intval($counts['orders']); ?>"><?php echo intval($counts['orders']); ?></span></div>
            </div>
            <div class="icon">🧾</div>
        </div>
        </a>

        <a href="admin_issues.php" style="text-decoration:none; color:inherit;">
        <div class="stat-card glass-card">
            <div>
                <div class="label">Open Issues</div>
                <div class="value"><span class="stat-value" data-value="<?php echo intval($counts['issues']); ?>"><?php echo intval($counts['issues']); ?></span></div>
            </div>
            <div class="icon">🚨</div>
        </div>
        </a>

        <div class="stat-card glass-card">
            <div>
                <div class="label">Total Revenue</div>
                                <div class="value">
                                        <span class="stat-currency">PKR</span>
                                        <span class="stat-value" data-value="<?php echo floatval($counts['revenue']); ?>"><?php echo number_format($counts['revenue'],2); ?></span>
                                        <span style="margin-left:12px; vertical-align:middle;">
                                            <span class="sparkline-wrap"><canvas id="sparkline" class="sparkline-canvas"></canvas></span>
                                        </span>
                                </div>
                                <div class="sub">Avg order: PKR <?php echo number_format($counts['avg_order'],2); ?>
                                        <?php if($revDeltaPct === null){ echo ''; } else { ?>
                                            &nbsp;|&nbsp; <strong style="color:<?php echo ($revDelta>=0?"var(--pinkish-purple)":"#ff9b9b"); ?>"><?php echo ($revDelta>=0?'+':'-'); ?>PKR <?php echo $revDeltaFmt; ?></strong>
                                            (<?php echo ($revDeltaPct===null? 'n/a' : ($revDeltaPct . '%')); ?>)
                                        <?php } ?>
                                </div>
            </div>
            <div class="icon">💰</div>
        </div>
    </section>

        <!-- Insights chart -->
        <section style="margin-top:22px;">
            <div class="chart-card glass-card">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
                    <h4 style="margin:0">Insights</h4>
                    <div class="range-toggle" role="tablist" aria-label="Select range">
                        <?php
                            $ranges = ['7d'=>'7d','30d'=>'30d','6m'=>'6m','all'=>'all'];
                            foreach($ranges as $k=>$v){
                                $active = ($range === $k) ? 'active' : '';
                                $label = ($k==='6m')? '6 months' : (($k==='all')? '12 months' : $k);
                                echo "<button class='{$active}' onclick=\"location.search='?range={$k}'\">{$label}</button>";
                            }
                        ?>
                    </div>
                </div>
                <?php
                    // fetch last 6 months revenue and orders
                    $labels = [];
                    $revData = [];
                    $ordersData = [];
                    $q = mysqli_query($conn, "SELECT DATE_FORMAT(created_at, '%Y-%m') as period, IFNULL(SUM(total_price),0) as revenue, COUNT(*) as cnt FROM orders GROUP BY period ORDER BY period DESC LIMIT 6");
                    $rows = [];
                    while($r = mysqli_fetch_assoc($q)) $rows[] = $r;
                    // reverse rows to chronological
                    $rows = array_reverse($rows);
                    if(empty($rows)){
                            // fallback: show zeroed last 6 months
                            for($i=5;$i>=0;$i--){ $m = date('Y-m', strtotime("-{$i} months")); $labels[] = $m; $revData[] = 0; $ordersData[] = 0; }
                    } else {
                            foreach($rows as $r){ $labels[] = $r['period']; $revData[] = floatval($r['revenue']); $ordersData[] = intval($r['cnt']); }
                    }
                ?>
                <div class="chart-canvas-wrap">
                    <canvas id="insightsChart"></canvas>
                </div>
                <div class="chart-gradient-legend">
                    <div class="chart-legend-item"><span class="chart-legend-swatch" style="background:linear-gradient(90deg,#C758B6,#7A3EA6)"></span> Revenue</div>
                    <div class="chart-legend-item"><span class="chart-legend-swatch" style="background:#C758B6"></span> Orders</div>
                </div>
            </div>
        </section>

</main>
<script>
// simple animated counter for stat-value elements
document.addEventListener('DOMContentLoaded', function(){
    function animateValue(el, start, end, duration) {
        const range = end - start;
        let startTime = null;
        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            const progress = Math.min((timestamp - startTime) / duration, 1);
            const value = Math.floor(start + range * progress);
            el.textContent = (end % 1 !== 0) ? (start + (end-start)*progress).toFixed(2) : value;
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        }
        window.requestAnimationFrame(step);
    }

    document.querySelectorAll('.stat-value').forEach(function(el){
        const raw = el.getAttribute('data-value') || el.textContent;
        const target = parseFloat(raw) || 0;
        animateValue(el, 0, target, 900);
    });
});
</script>

    <!-- Chart.js CDN and initialization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        const labels = <?php echo json_encode($labels); ?>;
        const rev = <?php echo json_encode($revData); ?>;
        const ord = <?php echo json_encode($ordersData); ?>;

        const ctx = document.getElementById('insightsChart');
        if(!ctx) return;
        const grad = ctx.getContext('2d').createLinearGradient(0,0,0,300);
        // use pinkish purple stops to match site primary
        grad.addColorStop(0, 'rgba(199,88,182,0.94)'); /* #C758B6 */
        grad.addColorStop(1, 'rgba(122,62,166,0.06)');  /* #7A3EA6 (soft) */

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Revenue',
                        data: rev,
                        backgroundColor: grad,
                        borderRadius: 8,
                        barPercentage: 0.6,
                        yAxisID: 'y',
                    },
                    {
                        type: 'line',
                        label: 'Orders',
                        data: ord,
                        borderColor: '#C758B6',
                        tension: 0.35,
                        pointRadius: 6,
                        pointBackgroundColor: '#fff',
                        pointBorderWidth: 3,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        position: 'nearest',
                                        callbacks: {
                                            label: function(context){
                                                const val = context.parsed.y !== undefined ? context.parsed.y : context.parsed;
                                                if(context.dataset.type === 'bar') return 'PKR ' + Number(val).toLocaleString();
                                                return context.dataset.label + ': ' + Number(val).toLocaleString();
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: { type: 'linear', position: 'left', ticks: { callback: function(v){ return 'PKR ' + Number(v).toLocaleString(); } } },
                                    y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false }, ticks: { callback: function(v){ return Number(v).toLocaleString(); } } }
                                },
                                animation: { duration: 800, easing: 'easeOutCubic' }
            }
        });

                // reveal the chart card with animation
                setTimeout(function(){
                    const ccard = document.querySelector('.chart-card'); if(ccard) ccard.classList.add('visible');
                }, 120);

                // render sparkline in revenue card
                const sp = document.getElementById('sparkline');
                if(sp){
                    const spCtx = sp.getContext('2d');
                    sp.width = 240; sp.height = 80; // high DPI friendly scaling
                    new Chart(spCtx, {
                        type: 'line',
                        data: { labels: labels, datasets: [{ data: rev, borderColor: '#C758B6', backgroundColor: 'rgba(199,88,182,0.08)', fill: true, tension:0.3, pointRadius:0 }] },
                        options: { responsive:false, maintainAspectRatio:false, scales:{ x:{ display:false }, y:{ display:false } }, plugins:{ legend:{display:false}, tooltip:{enabled:false} } }
                    });
                }
    });
    </script>

</body>
</html>

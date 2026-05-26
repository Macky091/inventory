<?php
/**
 * Dashboard Page
 * Displays key inventory metrics, recent products, and low-stock alerts.
 */

require_once __DIR__ . '/../includes/product.php';
require_once __DIR__ . '/../includes/user.php';

requireLogin();

// Determine whether to show account-scoped dashboard or global (admin)
$userId = isAdmin() ? null : ($_SESSION['user_id'] ?? null);

// ── Fetch dashboard data ────────────────────────────────────
$totalProducts    = getTotalProducts($userId);
$totalValue       = getTotalInventoryValue($userId);
$lowStockCount    = getLowStockCount($userId);
$outOfStockCount  = getOutOfStockCount($userId);
$recentProducts   = getRecentProducts($userId);
$lowStockProducts = getLowStockProducts($userId);
$totalUsers       = getTotalUsers();
$categoryData     = getProductsByCategory($userId);

// Prepare category chart data (JSON for inline JS)
$chartLabels = json_encode(array_column($categoryData, 'category_name'));
$chartCounts = json_encode(array_column($categoryData, 'count'));

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content">

  <!-- ── Stats Grid ───────────────────────────────────────── -->
  <div class="stats-grid">

    <div class="stat-card green">
      <div class="stat-icon">📦</div>
      <div class="stat-body">
        <div class="stat-value">
          <span data-target="<?= $totalProducts ?>">0</span>
        </div>
        <div class="stat-label">Total Products</div>
      </div>
    </div>

    <div class="stat-card blue">
      <div class="stat-icon">💰</div>
      <div class="stat-body">
        <div class="stat-value" style="font-size:1.35rem;">
          ₱<span data-target="<?= $totalValue ?>" data-float="true">0</span>
        </div>
        <div class="stat-label">Inventory Value</div>
      </div>
    </div>

    <div class="stat-card yellow">
      <div class="stat-icon">⚠️</div>
      <div class="stat-body">
        <div class="stat-value">
          <span data-target="<?= $lowStockCount ?>">0</span>
        </div>
        <div class="stat-label">Low Stock Items</div>
      </div>
    </div>

    <div class="stat-card red">
      <div class="stat-icon">🚫</div>
      <div class="stat-body">
        <div class="stat-value">
          <span data-target="<?= $outOfStockCount ?>">0</span>
        </div>
        <div class="stat-label">Out of Stock</div>
      </div>
    </div>

  </div><!-- /stats-grid -->

  <!-- ── Dashboard Grid ───────────────────────────────────── -->
  <div class="dashboard-grid">

    <!-- Recent Products -->
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title">
          <i class="fa-solid fa-clock-rotate-left" style="color:var(--accent);"></i>
          Recently Added
        </div>
        <a href="<?= BASE_URL ?>/index.php?page=products" class="btn btn-secondary btn-sm">
          View All
        </a>
      </div>

      <?php if (empty($recentProducts)): ?>
        <div class="empty-state">
          <div class="empty-icon">📭</div>
          <h4>No products yet</h4>
          <p>Add your first product to get started.</p>
        </div>
      <?php else: ?>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Qty</th>
                <th>Price</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentProducts as $p): ?>
              <tr>
                <td>
                  <div style="font-weight:600;"><?= sanitize($p['name']) ?></div>
                  <div class="td-muted"><?= sanitize($p['category_name'] ?? 'Uncategorized') ?></div>
                </td>
                <td><span class="sku-code"><?= sanitize($p['sku']) ?></span></td>
                <td>
                  <?php
                  $qty    = (int)$p['quantity'];
                  $reord  = (int)$p['reorder_level'];
                  $cls    = $qty === 0 ? 'badge-danger' : ($qty <= $reord ? 'badge-warning' : 'badge-success');
                  ?>
                  <span class="badge <?= $cls ?>"><?= $qty ?></span>
                </td>
                <td class="mono"><?= formatCurrency((float)$p['unit_price']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Low Stock Alerts -->
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title">
          <i class="fa-solid fa-triangle-exclamation" style="color:var(--warning);"></i>
          Low Stock Alerts
        </div>
        <?php if ($lowStockCount > 0): ?>
          <span class="badge badge-warning"><?= $lowStockCount ?> items</span>
        <?php endif; ?>
      </div>

      <?php if (empty($lowStockProducts)): ?>
        <div class="empty-state">
          <div class="empty-icon">✅</div>
          <h4>All stocked up!</h4>
          <p>No products are below their reorder level.</p>
        </div>
      <?php else: ?>
        <div style="padding:1rem 1.4rem;">
          <?php foreach ($lowStockProducts as $p):
            $qty   = (int)$p['quantity'];
            $reord = (int)$p['reorder_level'];
            $pct   = $reord > 0 ? min(100, round(($qty / $reord) * 100)) : 0;
            $fillCls = $qty === 0 ? 'low' : ($pct < 50 ? 'low' : 'mid');
          ?>
          <div style="margin-bottom:1.1rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.3rem;">
              <div>
                <span style="font-weight:600; font-size:.875rem;"><?= sanitize($p['name']) ?></span>
                <span class="sku-code" style="margin-left:.5rem;"><?= sanitize($p['sku']) ?></span>
              </div>
              <span class="badge <?= $qty === 0 ? 'badge-danger' : 'badge-warning' ?>">
                <?= $qty ?> / <?= $reord ?>
              </span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill <?= $fillCls ?>" style="width:<?= $pct ?>%;"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Category Breakdown Chart -->
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title">
          <i class="fa-solid fa-chart-pie" style="color:var(--info);"></i>
          Products by Category
        </div>
      </div>
      <div class="panel-body">
        <?php if (empty($categoryData)): ?>
          <div class="empty-state">
            <div class="empty-icon">📊</div>
            <h4>No data yet</h4>
          </div>
        <?php else: ?>
          <canvas id="categoryChart" style="max-height:260px;"></canvas>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick Summary -->
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title">
          <i class="fa-solid fa-circle-info" style="color:var(--accent);"></i>
          Quick Summary
        </div>
      </div>
      <div class="panel-body">
        <?php
        $rows = [
          ['icon'=>'📦','label'=>'Total Products',   'value'=>number_format($totalProducts)],
          ['icon'=>'💰','label'=>'Inventory Value',  'value'=>formatCurrency($totalValue)],
          ['icon'=>'⚠️','label'=>'Low Stock Items',  'value'=>number_format($lowStockCount)],
          ['icon'=>'🚫','label'=>'Out of Stock',     'value'=>number_format($outOfStockCount)],
          ['icon'=>'👥','label'=>'Registered Users', 'value'=>number_format($totalUsers)],
          ['icon'=>'🗂️','label'=>'Categories',       'value'=>count($categoryData)],
        ];
        ?>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:.85rem;">
          <?php foreach ($rows as $row): ?>
          <div style="background:var(--bg-primary); border:1px solid var(--border);
                      border-radius:var(--radius); padding:.85rem 1rem; display:flex;
                      align-items:center; gap:.6rem;">
            <span style="font-size:1.3rem;"><?= $row['icon'] ?></span>
            <div>
              <div style="font-size:.7rem; color:var(--text-muted); text-transform:uppercase;
                          letter-spacing:.5px;"><?= $row['label'] ?></div>
              <div style="font-weight:700; font-size:.95rem; font-family:'Space Mono',monospace;">
                <?= $row['value'] ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div><!-- /dashboard-grid -->
</div><!-- /page-content -->

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function () {
  const ctx = document.getElementById('categoryChart');
  if (!ctx) return;

  const labels = <?= $chartLabels ?>;
  const counts = <?= $chartCounts ?>;

  const palette = [
    '#00d084','#3b82f6','#ffbe0b','#ff4d6d',
    '#a855f7','#22d3ee','#f97316','#84cc16',
  ];

  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data:            counts,
        backgroundColor: palette.slice(0, labels.length),
        borderColor:     '#1e2330',
        borderWidth:     3,
        hoverOffset:     6,
      }],
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: '#8b95a7', font: { family: 'DM Sans', size: 12 }, padding: 16 },
        },
        tooltip: {
          callbacks: {
            label: ctx => ` ${ctx.label}: ${ctx.parsed} product(s)`,
          },
        },
      },
      cutout: '60%',
    },
  });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * Products Page
 * Lists all inventory products and handles CRUD actions via $_GET action param.
 *
 * Actions handled:
 *   store  — POST: insert a new product
 *   update — POST: update an existing product  (?id=)
 *   delete — POST: remove a product            (?id=)
 *   (none) — GET:  render the product list
 */

require_once __DIR__ . '/../includes/product.php';

requireLogin();

$action = $_GET['action'] ?? '';

/* ── Handle POST actions ─────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF guard
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid form submission. Please try again.');
        redirect('index.php?page=products');
    }

    /**
     * Build a sanitised product data array from POST input.
     * @return array
     */
    $buildData = function (): array {
        return [
            'name'          => trim($_POST['name']          ?? ''),
            'sku'           => strtoupper(trim($_POST['sku'] ?? '')),
            'description'   => trim($_POST['description']   ?? ''),
            'category_id'   => (int)($_POST['category_id']  ?? 0) ?: null,
            'quantity'      => max(0, (int)($_POST['quantity']     ?? 0)),
            'unit_price'    => max(0, (float)($_POST['unit_price'] ?? 0)),
            'reorder_level' => max(0, (int)($_POST['reorder_level'] ?? 10)),
            'created_by'    => (int)($_SESSION['user_id'] ?? 0),
        ];
    };

    /**
     * Validate shared product fields. Returns an array of error strings.
     * @param array $data
     * @return string[]
     */
    $validate = function (array $data): array {
        $errors = [];
        if (empty($data['name']))        $errors[] = 'Product name is required.';
        if (empty($data['sku']))         $errors[] = 'SKU is required.';
        elseif (!preg_match('/^[A-Z0-9\-_]+$/', $data['sku']))
                                         $errors[] = 'SKU must be alphanumeric (hyphens/underscores allowed).';
        if ($data['unit_price'] < 0)     $errors[] = 'Unit price cannot be negative.';
        return $errors;
    };

    // ── Store (create) ────────────────────────────────────────
    if ($action === 'store') {
        $data   = $buildData();
        $errors = $validate($data);

        if (!empty($errors)) {
            setFlash('error', implode(' ', $errors));
        } else {
            $result = createProduct($data);
            setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        redirect('index.php?page=products');
    }

    // ── Update (edit) ─────────────────────────────────────────
    if ($action === 'update') {
        $id   = (int)($_GET['id'] ?? 0);
        $data = $buildData();

        if ($id <= 0) {
            setFlash('error', 'Invalid product ID.');
        } else {
            $errors = $validate($data);
            if (!empty($errors)) {
                setFlash('error', implode(' ', $errors));
            } else {
                $result = updateProduct($id, $data);
                setFlash($result['success'] ? 'success' : 'error', $result['message']);
            }
        }
        redirect('index.php?page=products');
    }

    // ── Delete ────────────────────────────────────────────────
    if ($action === 'delete') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'Invalid product ID.');
        } else {
            $result = deleteProduct($id);
            setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        redirect('index.php?page=products');
    }
}

/* ── GET: List products ──────────────────────────────────────── */
$search     = trim($_GET['search']      ?? '');
$categoryId = (int)($_GET['category_id'] ?? 0);

$userId     = isAdmin() ? null : ($_SESSION['user_id'] ?? null);
$products   = getAllProducts($search, $categoryId, $userId);
$categories = getAllCategories();

$pageTitle  = 'Products';
$activePage = 'products';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content">

  <!-- Toolbar: search, filter, add button -->
  <div class="panel" style="margin-bottom:1.25rem;">
    <div class="panel-body" style="padding:1rem 1.4rem;">
      <div class="toolbar">

        <!-- Search box -->
        <div class="search-box">
          <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
          <input
            type="text"
            id="search-input"
            placeholder="Search by name or SKU…"
            value="<?= sanitize($search) ?>"
          />
        </div>

        <!-- Category filter -->
        <select id="category-filter" class="filter-select">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>"
              <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
              <?= sanitize($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <!-- Add product button -->
        <button class="btn btn-primary" onclick="openAddModal()">
          <i class="fa-solid fa-plus"></i> Add Product
        </button>

      </div>
    </div>
  </div>

  <!-- Products table -->
  <div class="panel">
    <div class="panel-header">
      <div class="panel-title">
        <i class="fa-solid fa-boxes-stacked" style="color:var(--accent);"></i>
        All Products
      </div>
      <span class="badge badge-default"><?= count($products) ?> item(s)</span>
    </div>

    <?php if (empty($products)): ?>
      <div class="empty-state" style="padding:3rem;">
        <div class="empty-icon">📦</div>
        <h4>No products found</h4>
        <p>
          <?php if ($search || $categoryId): ?>
            Try adjusting your search or filter.
          <?php else: ?>
            Click <strong>Add Product</strong> to create your first item.
          <?php endif; ?>
        </p>
      </div>

    <?php else: ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Product Name</th>
              <th>SKU</th>
              <th>Category</th>
              <th>Qty</th>
              <th>Unit Price</th>
              <th>Total Value</th>
              <th>Status</th>
              <th>Added</th>
              <th style="text-align:center;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $i => $p):
              $qty   = (int)$p['quantity'];
              $reord = (int)$p['reorder_level'];
              $price = (float)$p['unit_price'];

              // Stock status badge
              if ($qty === 0) {
                  $statusBadge = '<span class="badge badge-danger">Out of Stock</span>';
              } elseif ($qty <= $reord) {
                  $statusBadge = '<span class="badge badge-warning">Low Stock</span>';
              } else {
                  $statusBadge = '<span class="badge badge-success">In Stock</span>';
              }
            ?>
            <tr>
              <td class="td-muted"><?= $i + 1 ?></td>

              <td>
                <div style="font-weight:600;"><?= sanitize($p['name']) ?></div>
                <?php if (!empty($p['description'])): ?>
                  <div class="td-muted" style="max-width:200px; white-space:nowrap;
                       overflow:hidden; text-overflow:ellipsis;">
                    <?= sanitize($p['description']) ?>
                  </div>
                <?php endif; ?>
              </td>

              <td><span class="sku-code"><?= sanitize($p['sku']) ?></span></td>

              <td class="td-muted"><?= sanitize($p['category_name'] ?? 'Uncategorized') ?></td>

              <td>
                <span style="font-family:'Space Mono',monospace; font-weight:700;
                      color:<?= $qty === 0 ? 'var(--danger)' : ($qty <= $reord ? 'var(--warning)' : 'var(--text-primary)') ?>;">
                  <?= number_format($qty) ?>
                </span>
              </td>

              <td class="mono"><?= formatCurrency($price) ?></td>

              <td class="mono"><?= formatCurrency($qty * $price) ?></td>

              <td><?= $statusBadge ?></td>

              <td class="td-muted"><?= formatDate($p['created_at']) ?></td>

              <td>
                <div style="display:flex; gap:.4rem; justify-content:center;">
                  <!-- Edit -->
                  <button class="btn btn-warning btn-sm"
                    onclick='openEditModal(<?= json_encode([
                      "id"            => $p["id"],
                      "name"          => $p["name"],
                      "sku"           => $p["sku"],
                      "category_id"   => $p["category_id"],
                      "quantity"      => $p["quantity"],
                      "unit_price"    => $p["unit_price"],
                      "reorder_level" => $p["reorder_level"],
                      "description"   => $p["description"] ?? "",
                    ], JSON_HEX_QUOT | JSON_HEX_APOS) ?>)'>
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>
                  <!-- Delete -->
                  <button class="btn btn-danger btn-sm"
                    onclick='confirmDelete(<?= (int)$p["id"] ?>, <?= json_encode($p["name"]) ?>)'>
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

</div><!-- /page-content -->


<!-- ══════════════════════════════════════════════════════════
     ADD / EDIT PRODUCT MODAL
═══════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="productModal">
  <div class="modal">

    <div class="modal-header">
      <span class="modal-title" id="modal-title">Add New Product</span>
      <button class="modal-close" onclick="closeModal('productModal')">✕</button>
    </div>

    <form method="POST" id="product-form" action="">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>" />
      <input type="hidden" name="id"         id="product-id" />

      <div class="modal-body">

        <div class="form-row">
          <div class="form-group">
            <label><i class="fa-solid fa-tag"></i> Product Name *</label>
            <input type="text" name="name" id="product-name"
                   class="form-control" placeholder="e.g. Laptop Stand" required />
          </div>
          <div class="form-group">
            <label><i class="fa-solid fa-barcode"></i> SKU *</label>
            <input type="text" name="sku" id="product-sku"
                   class="form-control" placeholder="e.g. ELEC-001"
                   style="text-transform:uppercase;" required />
          </div>
        </div>

        <div class="form-group">
          <label><i class="fa-solid fa-layer-group"></i> Category</label>
          <select name="category_id" id="product-category" class="form-control">
            <option value="">— Select Category —</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label><i class="fa-solid fa-cubes"></i> Quantity *</label>
            <input type="number" name="quantity" id="product-quantity"
                   class="form-control" min="0" value="0" required />
          </div>
          <div class="form-group">
            <label><i class="fa-solid fa-peso-sign"></i> Unit Price (₱) *</label>
            <input type="number" name="unit_price" id="product-price"
                   class="form-control" min="0" step="0.01" value="0.00" required />
          </div>
        </div>

        <div class="form-group">
          <label>
            <i class="fa-solid fa-circle-exclamation"></i>
            Reorder Level
            <span style="color:var(--text-muted); font-weight:400; font-size:.8rem;">
              (alert when qty falls below this)
            </span>
          </label>
          <input type="number" name="reorder_level" id="product-reorder"
                 class="form-control" min="0" value="10" />
        </div>

        <div class="form-group">
          <label><i class="fa-solid fa-align-left"></i> Description</label>
          <textarea name="description" id="product-description"
                    class="form-control" placeholder="Optional product description…"></textarea>
        </div>

      </div><!-- /modal-body -->

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary"
                onclick="closeModal('productModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-floppy-disk"></i> Save Product
        </button>
      </div>

    </form>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     DELETE CONFIRMATION MODAL
═══════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal" style="max-width:420px;">

    <div class="modal-body" style="text-align:center; padding:2rem 1.5rem;">
      <div class="confirm-icon">🗑️</div>
      <h3 style="margin-bottom:.5rem;">Delete Product?</h3>
      <p style="color:var(--text-secondary); font-size:.9rem;">
        You are about to permanently delete
        <strong id="delete-product-name" style="color:var(--text-primary);"></strong>.
        This action cannot be undone.
      </p>
    </div>

    <div class="modal-footer" style="justify-content:center; gap:1rem;">
      <button class="btn btn-secondary" onclick="closeModal('deleteModal')">
        Cancel
      </button>
      <form method="POST" id="delete-form" action="" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>" />
        <button type="submit" class="btn btn-danger">
          <i class="fa-solid fa-trash"></i> Yes, Delete
        </button>
      </form>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

/**
 * Inventory Management System — Main JavaScript
 * Handles UI interactions: modals, clock, sidebar toggle, live search.
 */

/* ── Live Clock ─────────────────────────────────────────────── */
function updateClock() {
  const el = document.getElementById('live-clock');
  if (!el) return;
  const now = new Date();
  el.textContent = now.toLocaleTimeString('en-US', {
    hour:   '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
}
setInterval(updateClock, 1000);
updateClock();

/* ── Sidebar Mobile Toggle ──────────────────────────────────── */
const sidebar     = document.getElementById('sidebar');
const menuToggle  = document.getElementById('menu-toggle');
const sidebarOverlay = document.getElementById('sidebar-overlay');

if (menuToggle && sidebar) {
  menuToggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    if (sidebarOverlay) sidebarOverlay.classList.toggle('active');
  });
}

if (sidebarOverlay) {
  sidebarOverlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    sidebarOverlay.classList.remove('active');
  });
}

/* ── Modal Helpers ──────────────────────────────────────────── */

/**
 * Open a modal by ID.
 * @param {string} id  ID of the .modal-overlay element
 */
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('open');
}

/**
 * Close a modal by ID.
 * @param {string} id
 */
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('open');
}

// Close modal when clicking outside the modal box
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', function (e) {
    if (e.target === this) this.classList.remove('open');
  });
});

// Close modal on Escape key
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(el => {
      el.classList.remove('open');
    });
  }
});

/* ── Delete Confirmation Modal ──────────────────────────────── */

/**
 * Populate and open the delete-confirmation modal.
 * @param {number} productId
 * @param {string} productName
 */
function confirmDelete(productId, productName) {
  const modal = document.getElementById('deleteModal');
  if (!modal) return;

  modal.querySelector('#delete-product-name').textContent = productName;
  modal.querySelector('#delete-form').action =
    `index.php?page=products&action=delete&id=${productId}`;
  modal.classList.add('open');
}

/* ── Product Form Modal ─────────────────────────────────────── */

/**
 * Open the Add Product modal (blank form).
 */
function openAddModal() {
  const modal = document.getElementById('productModal');
  if (!modal) return;

  modal.querySelector('#modal-title').textContent     = '➕ Add New Product';
  modal.querySelector('#product-form').action         = 'index.php?page=products&action=store';
  modal.querySelector('#product-form').reset();
  modal.querySelector('#product-id').value            = '';
  modal.classList.add('open');
}

/**
 * Open the Edit Product modal and pre-fill fields.
 * @param {Object} product  Product data object
 */
function openEditModal(product) {
  const modal = document.getElementById('productModal');
  if (!modal) return;

  modal.querySelector('#modal-title').textContent     = '✏️ Edit Product';
  modal.querySelector('#product-form').action         = `index.php?page=products&action=update&id=${product.id}`;
  modal.querySelector('#product-id').value            = product.id;
  modal.querySelector('#product-name').value          = product.name;
  modal.querySelector('#product-sku').value           = product.sku;
  modal.querySelector('#product-category').value      = product.category_id || '';
  modal.querySelector('#product-quantity').value      = product.quantity;
  modal.querySelector('#product-price').value         = product.unit_price;
  modal.querySelector('#product-reorder').value       = product.reorder_level;
  modal.querySelector('#product-description').value   = product.description || '';
  modal.classList.add('open');
}

/* ── Live Search & Category Filter ─────────────────────────── */

const searchInput    = document.getElementById('search-input');
const categoryFilter = document.getElementById('category-filter');

/**
 * Rebuild the query string with the current search and filter values,
 * then navigate to the updated URL (triggers a PHP-side filter).
 */
function applyFilter() {
  const search   = searchInput   ? searchInput.value.trim()   : '';
  const category = categoryFilter ? categoryFilter.value : '';
  const params   = new URLSearchParams({ page: 'products' });

  if (search)   params.set('search', search);
  if (category) params.set('category_id', category);

  window.location.href = 'index.php?' + params.toString();
}

// Debounced search: submit after 400 ms of inactivity
let searchTimer;
if (searchInput) {
  searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilter, 400);
  });
}

if (categoryFilter) {
  categoryFilter.addEventListener('change', applyFilter);
}

/* ── Flash-message auto-dismiss ─────────────────────────────── */
const flashMsg = document.querySelector('.alert[data-auto-dismiss]');
if (flashMsg) {
  setTimeout(() => {
    flashMsg.style.transition = 'opacity 0.4s ease';
    flashMsg.style.opacity    = '0';
    setTimeout(() => flashMsg.remove(), 400);
  }, 4000);
}

/* ── Animate stat counters on page load ─────────────────────── */
function animateCounter(el) {
  const target  = parseFloat(el.dataset.target) || 0;
  const isFloat = el.dataset.float === 'true';
  const prefix  = el.dataset.prefix || '';
  const duration = 900;
  const start    = performance.now();

  function tick(now) {
    const elapsed = now - start;
    const progress = Math.min(elapsed / duration, 1);
    const eased    = 1 - Math.pow(1 - progress, 3); // ease-out cubic
    const value    = target * eased;

    el.textContent = prefix + (isFloat
      ? value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      : Math.floor(value).toLocaleString());

    if (progress < 1) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}

document.querySelectorAll('[data-target]').forEach(el => animateCounter(el));

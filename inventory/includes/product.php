<?php
/**
 * Product Model
 * Handles all database operations related to inventory products.
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Retrieve all products with their category names.
 * Supports optional search and category filtering.
 *
 * @param string $search      Search term (matches name or SKU)
 * @param int    $categoryId  Filter by category (0 = all)
 * @return array
 */
function getAllProducts(string $search = '', int $categoryId = 0, ?int $userId = null): array {
    $conn = getDBConnection();

    $sql = '
        SELECT
            p.*,
            c.name AS category_name,
            u.full_name AS created_by_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u      ON p.created_by  = u.id
        WHERE 1=1
    ';

    $params = [];
    $types  = '';

    if ($search !== '') {
        $sql     .= ' AND (p.name LIKE ? OR p.sku LIKE ?)';
        $like     = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $types   .= 'ss';
    }

    if ($categoryId > 0) {
        $sql     .= ' AND p.category_id = ?';
        $params[] = $categoryId;
        $types   .= 'i';
    }

    if ($userId !== null) {
        $sql     .= ' AND p.created_by = ?';
        $params[] = $userId;
        $types   .= 'i';
    }

    $sql .= ' ORDER BY p.created_at DESC';

    $stmt = $conn->prepare($sql);

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    $conn->close();
    return $products;
}

/**
 * Retrieve a single product by its primary key.
 *
 * @param int $id Product ID
 * @return array|null  Product row or null if not found
 */
function getProductById(int $id): ?array {
    $conn = getDBConnection();

    $stmt = $conn->prepare('
        SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
        LIMIT 1
    ');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $result  = $stmt->get_result();
    $product = $result->num_rows > 0 ? $result->fetch_assoc() : null;

    $stmt->close();
    $conn->close();
    return $product;
}

/**
 * Insert a new product record.
 *
 * @param array $data   Associative array with keys:
 *                      name, sku, description, category_id,
 *                      quantity, unit_price, reorder_level, created_by
 * @return array{success: bool, message: string}
 */
function createProduct(array $data): array {
    $conn = getDBConnection();

    // Verify SKU is unique
    $check = $conn->prepare('SELECT id FROM products WHERE sku = ? LIMIT 1');
    $check->bind_param('s', $data['sku']);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        $conn->close();
        return ['success' => false, 'message' => 'A product with this SKU already exists.'];
    }
    $check->close();

    $stmt = $conn->prepare('
        INSERT INTO products
            (name, sku, description, category_id, quantity, unit_price, reorder_level, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->bind_param(
        'sssiiidi',
        $data['name'],
        $data['sku'],
        $data['description'],
        $data['category_id'],
        $data['quantity'],
        $data['unit_price'],
        $data['reorder_level'],
        $data['created_by']
    );

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'Product added successfully.'];
    }

    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    return ['success' => false, 'message' => 'Failed to add product: ' . $error];
}

/**
 * Update an existing product record.
 *
 * @param int   $id   Product ID to update
 * @param array $data Fields to update (same keys as createProduct)
 * @return array{success: bool, message: string}
 */
function updateProduct(int $id, array $data): array {
    $conn = getDBConnection();

    // Verify SKU is unique, excluding the current product
    $check = $conn->prepare('SELECT id FROM products WHERE sku = ? AND id != ? LIMIT 1');
    $check->bind_param('si', $data['sku'], $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        $conn->close();
        return ['success' => false, 'message' => 'Another product with this SKU already exists.'];
    }
    $check->close();

    $stmt = $conn->prepare('
        UPDATE products
        SET name          = ?,
            sku           = ?,
            description   = ?,
            category_id   = ?,
            quantity      = ?,
            unit_price    = ?,
            reorder_level = ?
        WHERE id = ?
    ');
    $stmt->bind_param(
        'sssiiidi',
        $data['name'],
        $data['sku'],
        $data['description'],
        $data['category_id'],
        $data['quantity'],
        $data['unit_price'],
        $data['reorder_level'],
        $id
    );

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'Product updated successfully.'];
    }

    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    return ['success' => false, 'message' => 'Failed to update product: ' . $error];
}

/**
 * Delete a product record by ID.
 *
 * @param int $id Product ID
 * @return array{success: bool, message: string}
 */
function deleteProduct(int $id): array {
    $conn = getDBConnection();

    $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
    $stmt->bind_param('i', $id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'Product deleted successfully.'];
    }

    $error = $stmt->error;
    $stmt->close();
    $conn->close();

    if ($error) {
        return ['success' => false, 'message' => 'Failed to delete product: ' . $error];
    }
    return ['success' => false, 'message' => 'Product not found.'];
}

/**
 * Retrieve all product categories.
 *
 * @return array
 */
function getAllCategories(): array {
    $conn       = getDBConnection();
    $result     = $conn->query('SELECT * FROM categories ORDER BY name ASC');
    $categories = $result->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    return $categories;
}

// ── Dashboard statistics ─────────────────────────────────────

/**
 * Count total products in the inventory.
 *
 * @return int
 */
function getTotalProducts(?int $userId = null): int {
    $conn = getDBConnection();
    if ($userId === null) {
        $result = $conn->query('SELECT COUNT(*) AS total FROM products');
        $row    = $result->fetch_assoc();
        $conn->close();
        return (int) $row['total'];
    }

    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM products WHERE created_by = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return (int) $row['total'];
}

/**
 * Sum the total inventory value (quantity × unit_price).
 *
 * @return float
 */
function getTotalInventoryValue(?int $userId = null): float {
    $conn = getDBConnection();
    if ($userId === null) {
        $result = $conn->query('SELECT COALESCE(SUM(quantity * unit_price), 0) AS total FROM products');
        $row    = $result->fetch_assoc();
        $conn->close();
        return (float) $row['total'];
    }

    $stmt = $conn->prepare('SELECT COALESCE(SUM(quantity * unit_price), 0) AS total FROM products WHERE created_by = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return (float) $row['total'];
}

/**
 * Count products where quantity ≤ reorder_level (low stock).
 *
 * @return int
 */
function getLowStockCount(?int $userId = null): int {
    $conn = getDBConnection();
    if ($userId === null) {
        $result = $conn->query('SELECT COUNT(*) AS total FROM products WHERE quantity <= reorder_level');
        $row    = $result->fetch_assoc();
        $conn->close();
        return (int) $row['total'];
    }

    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM products WHERE quantity <= reorder_level AND created_by = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return (int) $row['total'];
}

/**
 * Count products with quantity = 0 (out of stock).
 *
 * @return int
 */
function getOutOfStockCount(?int $userId = null): int {
    $conn = getDBConnection();
    if ($userId === null) {
        $result = $conn->query('SELECT COUNT(*) AS total FROM products WHERE quantity = 0');
        $row    = $result->fetch_assoc();
        $conn->close();
        return (int) $row['total'];
    }

    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM products WHERE quantity = 0 AND created_by = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return (int) $row['total'];
}

/**
 * Retrieve the 5 most recently added products for the dashboard.
 *
 * @return array
 */
function getRecentProducts(?int $userId = null): array {
    $conn = getDBConnection();
    if ($userId === null) {
        $result = $conn->query(
            'SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             ORDER BY p.created_at DESC
             LIMIT 5'
        );
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $conn->close();
        return $rows;
    }

    $stmt = $conn->prepare(
        'SELECT p.*, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.created_by = ?
         ORDER BY p.created_at DESC
         LIMIT 5'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $rows;
}

/**
 * Retrieve the 5 products with the lowest stock quantities.
 *
 * @return array
 */
function getLowStockProducts(?int $userId = null): array {
    $conn = getDBConnection();
    if ($userId === null) {
        $result = $conn->query(
            'SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.quantity <= p.reorder_level
             ORDER BY p.quantity ASC
             LIMIT 5'
        );
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $conn->close();
        return $rows;
    }

    $stmt = $conn->prepare(
        'SELECT p.*, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.quantity <= p.reorder_level AND p.created_by = ?
         ORDER BY p.quantity ASC
         LIMIT 5'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $rows;
}

/**
 * Get product count grouped by category for charts.
 *
 * @return array  Each row: {category_name, count}
 */
function getProductsByCategory(?int $userId = null): array {
    $conn = getDBConnection();
    if ($userId === null) {
        $result = $conn->query(
            'SELECT
                COALESCE(c.name, "Uncategorized") AS category_name,
                COUNT(p.id) AS count
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            GROUP BY c.id
            ORDER BY count DESC'
        );
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $conn->close();
        return $rows;
    }

    $stmt = $conn->prepare(
        'SELECT
            COALESCE(c.name, "Uncategorized") AS category_name,
            COUNT(p.id) AS count
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.created_by = ?
         GROUP BY c.id
         ORDER BY count DESC'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $rows;
}

-- ============================================================
-- Inventory Management System — Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS inventory_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE inventory_db;

-- ------------------------------------------------------------
-- Table: users
-- Stores registered user accounts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    username    VARCHAR(50)     NOT NULL UNIQUE,
    email       VARCHAR(120)    NOT NULL UNIQUE,
    password    VARCHAR(255)    NOT NULL,             -- bcrypt hash
    full_name   VARCHAR(100)    NOT NULL,
    role        ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_email    (email),
    INDEX idx_username (username)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: categories
-- Product categories for grouping inventory items
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name        VARCHAR(80)     NOT NULL UNIQUE,
    description TEXT,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: products
-- Core inventory items
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id            INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    category_id   INT UNSIGNED,
    name          VARCHAR(120)      NOT NULL,
    sku           VARCHAR(60)       NOT NULL UNIQUE,
    description   TEXT,
    quantity      INT               NOT NULL DEFAULT 0,
    unit_price    DECIMAL(10, 2)    NOT NULL DEFAULT 0.00,
    reorder_level INT               NOT NULL DEFAULT 10,  -- low-stock threshold
    created_by    INT UNSIGNED,
    created_at    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_sku         (sku),
    INDEX idx_category    (category_id),
    INDEX idx_name        (name),
    CONSTRAINT fk_product_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON DELETE SET NULL,
    CONSTRAINT fk_product_creator
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Seed: default categories
-- ------------------------------------------------------------
INSERT INTO categories (name, description) VALUES
    ('Electronics',  'Electronic devices and components'),
    ('Clothing',     'Apparel and garments'),
    ('Food & Beverage', 'Consumable food and drink products'),
    ('Office Supplies', 'Stationery and office materials'),
    ('Tools & Hardware', 'Hand tools and hardware equipment')
ON DUPLICATE KEY UPDATE name = name;

-- ------------------------------------------------------------
-- Seed: default admin account  (password: Admin@1234)
-- ------------------------------------------------------------
INSERT INTO users (username, email, password, full_name, role) VALUES (
    'admin',
    'admin@inventory.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Admin@1234
    'System Administrator',
    'admin'
) ON DUPLICATE KEY UPDATE username = username;

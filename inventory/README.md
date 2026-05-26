# InvenTrack — Inventory Management System

A dynamic, database-driven PHP web application built as a course project to
demonstrate full-stack web development and database integration concepts.

---

## Project Description

InvenTrack is a fully functional inventory management system that allows users
to register, log in, and manage product stock through a clean, modern web
interface. It implements all four fundamental SQL operations (Create, Read,
Update, Delete) and includes several dynamic features such as session-based
authentication, CSRF protection, live search filtering, and a real-time
dashboard with chart visualisations.

---

## Technologies Used

| Layer       | Technology                            |
|-------------|---------------------------------------|
| Backend     | PHP 8.x (procedural, no framework)    |
| Database    | MySQL 8 / MariaDB 10                  |
| Frontend    | HTML5, CSS3, Vanilla JavaScript        |
| Charts      | Chart.js 4 (CDN)                      |
| Icons       | Font Awesome 6 (CDN)                  |
| Fonts       | DM Sans + Space Mono (Google Fonts)   |
| Server      | Apache / Nginx (XAMPP / WAMP / LAMP)  |

---

## Features

### Authentication
- User **registration** with full input validation
- Secure **login** using `password_hash` / `password_verify` (bcrypt, cost 12)
- **Session management** with `session_regenerate_id` on login
- **CSRF token** validation on all POST forms
- **Logout** with full session destruction

### Dashboard
- Live stat cards: total products, inventory value, low-stock count, out-of-stock count
- Animated counters on page load
- Doughnut chart — products by category (Chart.js)
- Recently added products table
- Low-stock progress bars with colour coding
- Live clock (Space Mono font, JS interval)

### Products (CRUD)
- **View All** — paginated/filterable table with status badges
- **Add** — modal form with validation (name, SKU uniqueness, price ≥ 0)
- **Edit** — pre-populated modal, same validation
- **Delete** — confirmation modal, CSRF protected
- **Live search** by name or SKU (debounced, 400 ms)
- **Category filter** — instant redirect with URL query params
- Stock status badges: In Stock / Low Stock / Out of Stock
- Total inventory value per row (qty × price)

### Dynamic Features (≥ 3 required)
1. **Session-based authentication** — protected routes via `requireLogin()`
2. **Live search & category filter** — debounced JS + PHP server-side query
3. **CSRF protection** — token generation and verification on every form
4. **Role management** — admin vs. staff; admin-only Users page
5. **Flash messages** — auto-dismiss after 4 s (CSS + JS fade)
6. **Animated stat counters** — `requestAnimationFrame` ease-out cubic

---

## Database Structure

```sql
inventory_db
├── users
│   ├── id (PK)
│   ├── username (UNIQUE)
│   ├── email (UNIQUE)
│   ├── password (bcrypt)
│   ├── full_name
│   ├── role  ENUM('admin','staff')
│   └── created_at / updated_at
│
├── categories
│   ├── id (PK)
│   ├── name (UNIQUE)
│   └── description
│
└── products
    ├── id (PK)
    ├── category_id (FK → categories)
    ├── name
    ├── sku (UNIQUE)
    ├── description
    ├── quantity
    ├── unit_price
    ├── reorder_level
    ├── created_by (FK → users)
    └── created_at / updated_at
```

---

## Setup Instructions

### Prerequisites
- PHP 8.0+
- MySQL 8 or MariaDB 10+
- Apache / Nginx (XAMPP, WAMP, or LAMP recommended)

### Steps

1. **Clone or copy** the project folder into your web server's document root.
   - XAMPP: `C:\xampp\htdocs\inventory\`
   - WAMP:  `C:\wamp64\www\inventory\`
   - Linux: `/var/www/html/inventory/`

2. **Import the database schema**
   ```bash
   mysql -u root -p < config/schema.sql
   ```
   Or paste the contents of `config/schema.sql` into phpMyAdmin's SQL tab.

3. **Configure the database connection** (if needed) in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'inventory_db');
   ```

4. **Open in browser**:
   ```
   http://localhost/inventory/
   ```

5. **Log in** with the seeded admin account:
   - Username: `admin`
   - Password: `Admin@1234`

---

## Project File Structure

```
inventory/
├── index.php                  ← Front controller (router)
├── config/
│   ├── database.php           ← DB connection + constants
│   └── schema.sql             ← Database DDL + seed data
├── includes/
│   ├── functions.php          ← Auth, session, CSRF, helpers
│   ├── user.php               ← User model (register, login, logout)
│   ├── product.php            ← Product model (CRUD + dashboard stats)
│   ├── header.php             ← Shared HTML header + sidebar
│   └── footer.php             ← Shared HTML footer + JS
├── pages/
│   ├── login.php              ← Login form + processing
│   ├── register.php           ← Registration form + processing
│   ├── dashboard.php          ← Dashboard page
│   ├── products.php           ← Product CRUD page
│   └── users.php              ← Admin — user list
└── assets/
    ├── css/
    │   └── style.css          ← Global stylesheet (CSS variables, components)
    └── js/
        └── main.js            ← UI interactions (modals, clock, search, counters)
```

---

## Security Notes

- All user inputs are validated server-side before any database operation.
- Passwords are hashed with `PASSWORD_BCRYPT` (cost 12) — never stored plain.
- All output is escaped via `htmlspecialchars()` (the `sanitize()` helper).
- All SQL uses **prepared statements** with bound parameters — no raw interpolation.
- Session IDs are regenerated on login to prevent session fixation.
- CSRF tokens protect every state-changing form.

---

*Developed as a course project — Web Development & Database Integration*

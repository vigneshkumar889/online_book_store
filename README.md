# Online Book Store

A dynamic Online Book Store built with **HTML, CSS, JavaScript, PHP and MySQL** —
designed to run locally with **XAMPP/WAMP** and **VS Code**. No frameworks, no APIs,
no external libraries.

## Features

**Customers**
- Browse, search (title/author), filter (category + price), and sort (price) books
- View book details, register, login, logout
- Add to cart / Buy Now, with a login redirect if not logged in
- Save/edit a delivery address
- Checkout and place orders (stock is validated and reduced server-side)
- View their own past orders and order items

**Admin**
- Separate login at `admin/login.php` (not linked from the customer nav)
- Dashboard with live totals (books, customers, orders, revenue) and recent orders
- Manage books (add/edit/delete, image upload, category assignment, stock)
- Manage categories (add/edit/delete — blocked if books still use the category)
- View customers (read-only list)
- View & update order status (Pending / Confirmed / Delivered / Cancelled)

## Local Setup (XAMPP)

1. **Install XAMPP** (or WAMP) if you haven't already.
2. **Start Apache and MySQL** from the XAMPP control panel.
3. **Copy this folder** into your web root, e.g.:
   ```
   xampp/htdocs/online-book-store/
   ```
4. **Import the database**:
   - Open `http://localhost/phpMyAdmin`
   - Create/Import the SQL file at `database/bookstore.sql`
     (this creates the `bookstore` database, all tables, and sample data)
5. **Check the DB connection** in `config/database.php` — the defaults
   (`localhost` / `root` / no password / `bookstore`) match a fresh XAMPP install.
6. **Open the site**:
   - Customer site: `http://localhost/online-book-store/`
   - Admin panel: `http://localhost/online-book-store/admin/login.php`

## Default Admin Login

```
Email:    admin@bookstore.com
Password: admin123
```

## Project Structure

```
online-book-store/
├── index.php, books.php, book-details.php, categories.php, search.php
├── register.php, login.php, logout.php
├── cart.php, checkout.php, my-orders.php
├── admin/            (separate admin panel + its own includes/auth.php guard)
├── config/database.php   (single shared MySQLi connection)
├── includes/         (shared header/footer/functions for customer pages)
├── css/style.css, css/admin.css
├── js/script.js
├── images/           (book cover images; admin uploads land here)
└── database/bookstore.sql
```

## Notes

- Passwords are hashed with `password_hash()` / verified with `password_verify()`.
- All SQL queries use prepared statements (no raw string concatenation).
- Order totals are always recalculated on the server before saving — the
  browser's numbers are never trusted.
- There is intentionally **no real payment gateway** — this is a lab project.
- Uploaded book images are validated by extension and stored in `images/`.

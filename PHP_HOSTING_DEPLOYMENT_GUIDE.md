# FR-KING AURA — Hosting Server (PHP + MySQL) Deployment Guide

This guide explains how to host FR-KING AURA on your own **hosting server (cPanel, Hostinger, Namecheap, Bluehost, VPS, Apache, or LiteSpeed)** using **PHP** and your **hosting server's MySQL database**.

---

## 📁 What Was Created for PHP & MySQL

| File | Purpose |
|---|---|
| **`api/config.php`** | Hosting server database credentials (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) and admin password. |
| **`api/db.php`** | PDO MySQL connection, auto-table creation, device locking, and key verification. |
| **`api/index.php`** | Unified PHP API router handling all `/api/...` endpoints with CORS and JSON support. |
| **`database.sql`** | Complete MySQL schema with `licenses`, `signals`, `app_config`, and pre-approved VIP keys. |
| **`.htaccess`** | Apache/LiteSpeed rewrite rules routing `/api/*` to PHP and frontend routes to `index.html`. |
| **`.npmrc`** | Fixed peer dependency resolution for clean npm builds without ERESOLVE conflicts. |

---

## 🚀 Step-by-Step Hosting Setup (cPanel / Hostinger / Shared Hosting)

### Step 1: Create Your MySQL Database in cPanel
1. Log in to your hosting **cPanel**.
2. Go to **MySQL Databases** (or **Database Wizard**).
3. Create a new database (e.g., `user_frking_db`).
4. Create a MySQL user with a strong password and add the user to the database with **ALL PRIVILEGES**.

### Step 2: Import `database.sql`
1. Open **phpMyAdmin** from your cPanel.
2. Select your newly created database on the left.
3. Click the **Import** tab at the top.
4. Click **Choose File**, select `database.sql` from this project, and click **Import** (or **Go**).
5. All tables (`licenses`, `signals`, `app_config`) and pre-approved VIP keys will be imported immediately.

### Step 3: Configure Database in `api/config.php`
Open `api/config.php` and enter your database credentials:

```php
define('DB_HOST', 'localhost');                  // Usually 'localhost' on cPanel
define('DB_NAME', 'your_cpanel_username_dbname'); // Your database name
define('DB_USER', 'your_cpanel_username_dbuser'); // Your database username
define('DB_PASS', 'your_database_password');     // Your database password
define('DB_PORT', 3306);

// Admin Control Panel Password
define('ADMIN_PASSWORD', 'FRK@2026#WaqasKing!Secure9x');
define('MASTER_API_KEY', 'FRK-PRO-MASTER-KEY-2026');
```

### Step 4: Build & Upload to `public_html`
1. Build the production frontend:
   ```bash
   npm run build
   ```
2. Open your hosting **File Manager** (or connect via FTP) and open `public_html/`.
3. Upload the contents of the `dist/` folder into `public_html/`.
4. Upload the `api/` folder into `public_html/api/`.
5. Ensure `.htaccess` is present in `public_html/`.

Your directory structure in `public_html/` will look like:
```
public_html/
├── api/
│   ├── config.php
│   ├── db.php
│   ├── index.php
│   ├── .htaccess
│   └── ...
├── assets/
├── fr-kings-logo.jpg
├── icon.svg
├── index.html
├── manifest.json
└── .htaccess
```

---

## 🧪 Testing Your Live Hosting Backend

Once uploaded, test your API directly in your browser:

1. **Health Check**:
   Open:
   ```
   https://yourdomain.com/api/health
   ```
   Response:
   ```json
   {
     "ok": true,
     "service": "FR-KING AURA PHP Backend",
     "status": "online",
     "database": "MySQL Connected"
   }
   ```

2. **Terminal Frontend**:
   Open:
   ```
   https://yourdomain.com/
   ```
   - Enter any pre-approved key (e.g. `FRK-VIP-LIFETIME-KING`, `FRK-CROWN-1YEAR-8820`).
   - The React frontend connects directly to your hosting server's PHP API and MySQL database!

3. **Admin Panel**:
   - Access `https://yourdomain.com/admin`
   - Password: `FRK@2026#WaqasKing!Secure9x`

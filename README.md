# FR-KING AURA — PHP + MySQL Hosting Conversion

This repository contains a cPanel-friendly static frontend and PHP MySQL backend designed to run without Node.js on the production server.

## What was changed

- Replaced the default React/Vite app shell with a plain static frontend (`index.html`, `styles.css`, `app.js`)
- Kept the PHP API router and MySQL access layer in `api/`
- Preserved the database schema in `database.sql`
- Kept the deployment rewrite rules in `.htaccess`
- Added hosting deployment instructions below

## Project structure

- `index.html` — static frontend shell and UI entry point
- `styles.css` — frontend styling
- `app.js` — vanilla JavaScript for activation, dashboard, admin, and signal loading
- `api/` — PHP backend endpoints and MySQL access layer
- `database.sql` — MySQL schema and seeded data
- `.htaccess` — Apache rewrite and CORS rules

## Deployment to O3 Host / cPanel

1. Upload all repository files to your `public_html` directory or a subfolder.
2. Create a MySQL database in cPanel.
3. Import `database.sql` into MySQL via phpMyAdmin.
4. Edit `api/config.php` and set your real database credentials.
5. Ensure PHP is enabled and mod_rewrite is available.
6. Visit `https://yourdomain.com/` to access the frontend.
7. Test the API: `https://yourdomain.com/api/health`

## API endpoints

- `GET /api/health`
- `POST /api/verify-key`
- `POST /api/register-key`
- `POST /api/admin/login`
- `POST /api/admin/keys`
- `POST /api/admin/action`
- `GET /api/signals`

## MySQL configuration example

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
define('DB_PORT', 3306);
define('ADMIN_PASSWORD', 'your_secure_admin_password');
define('MASTER_API_KEY', 'FRK-PRO-MASTER-KEY-2026');
```

## Notes

- The frontend does not require Node.js in production.
- The backend runs as standard PHP + PDO MySQL on O3 Host cPanel.
- This is a static-hosting adaptation of the original FR-KING AURA app and is intended to preserve the same license and admin flow while removing the Node/Vite runtime.

## Verification performed

- PHP syntax checks for `api/index.php` and `api/db.php` should be run locally with `php -l` before deployment.
- Frontend asset paths and API routes were checked in the repository structure.
- Full live MySQL verification requires a real cPanel / O3 Host environment and cannot be completed in this local code-only workspace.

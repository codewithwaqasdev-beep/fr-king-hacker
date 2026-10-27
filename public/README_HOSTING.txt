================================================================================
  FR-KING AURA — LIVE SERVER HOSTING DEPLOYMENT GUIDE (cPanel / Apache / VPS)
================================================================================

This package contains ONLY the production live-server files. 
No node_modules, no raw TypeScript, no dev tools — 100% ready for live hosting.

--------------------------------------------------------------------------------
1. ZIP CONTENTS MANIFEST
--------------------------------------------------------------------------------
- index.html        : Production compiled React frontend (with relative paths)
- assets/           : Optimized JS and CSS bundles
- api/              : PHP Backend REST API
    ├── index.php         (Main API router)
    ├── config.php        (Database & security settings)
    ├── db.php            (PDO MySQL connector)
    ├── register-key.php  (User key registration)
    ├── verify-key.php    (Key validation & device binding)
    ├── signals.php       (Signal generator & history)
    ├── health.php        (Server health check)
    └── .htaccess         (API rewrite and CORS rules)
- install.php       : 1-Click Browser Database Installer & Table Seeder
- database.sql      : Raw MySQL database schema (if importing via phpMyAdmin)
- .htaccess         : Root Apache routing (routes /api to PHP, all else to SPA)
- fr-kings-logo.jpg : Official brand logo
- icon.svg          : App favicon / app icon
- manifest.json     : PWA manifest

--------------------------------------------------------------------------------
2. QUICK 3-STEP INSTALLATION
--------------------------------------------------------------------------------
STEP 1: UPLOAD TO YOUR HOSTING
  - Log in to your cPanel / DirectAdmin / CyberPanel / VPS.
  - Open File Manager -> go to "public_html" (or your subdomain folder).
  - Upload the contents of this ZIP file directly into public_html.
  - Extract if uploaded as a zip.

STEP 2: RUN THE 1-CLICK WEB INSTALLER
  - In your web browser, open:
      https://your-domain.com/install.php
  - Enter your MySQL Database details:
      * DB Host: localhost (or your server IP)
      * DB Name: your_database_name
      * DB User: your_database_username
      * DB Password: your_database_password
  - Click "START 1-CLICK INSTALLATION".
  - The installer will automatically:
      ✓ Test MySQL database connection
      ✓ Create all tables (licenses, signals, app_config, audit_logs)
      ✓ Seed initial VIP lifetime keys
      ✓ Save settings to api/config.php

STEP 3: SECURE YOUR INSTALLATION
  - Delete `install.php` from your server after successful installation.

--------------------------------------------------------------------------------
3. ADMIN CREDENTIALS & ACCESS
--------------------------------------------------------------------------------
* Admin Panel URL : https://your-domain.com/admin
                    (Or click the FR-KING logo 5 times on the activation screen)
* Admin Password  : FRK@2026#WaqasKing!Secure9x
* Master API Key  : FRK-PRO-MASTER-KEY-2026

--------------------------------------------------------------------------------
4. TROUBLESHOOTING
--------------------------------------------------------------------------------
Q: Why would I see a blank page?
A: 1. Ensure .htaccess is uploaded (some FTP clients hide dotfiles like .htaccess).
   2. Ensure mod_rewrite and mod_headers are enabled in Apache.
   3. Check PHP version is 7.4 or higher (PHP 8.0, 8.1, 8.2, 8.3 supported).

Q: How to import the database manually?
A: Go to cPanel -> phpMyAdmin -> select your database -> click "Import" -> 
   choose `database.sql` -> click "Go".
   Then open `api/config.php` and enter your DB credentials.

================================================================================
Support / Developer Contact: @codetrader (FR-KING AI Team)
================================================================================

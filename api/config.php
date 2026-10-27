<?php
/**
 * FR-KING AURA — Hosting Server Database & App Configuration
 * Configure your cPanel / Shared Hosting / VPS MySQL Database here.
 */

// Hosting Server MySQL Database Credentials
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'frking_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: 3306);

// Security & Administration
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'FRK@2026#WaqasKing!Secure9x');
define('MASTER_API_KEY', getenv('API_KEY') ?: 'FRK-PRO-MASTER-KEY-2026');
define('ALLOW_MASTER_KEY_BYPASS', true);

// App Contact Information
define('RECOVERY_EMAIL', 'emailwaqas7@gmail.com');
define('WHATSAPP_URL', 'https://chat.whatsapp.com/G1yKmNVIntiLx7c9KX5zEu');
define('TELEGRAM_URL', 'https://t.me/frking00');
define('PHONE', '+966554573612');
define('DEVELOPER', '@codetrader');

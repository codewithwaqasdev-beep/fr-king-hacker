<?php
/**
 * FR-KING AURA — 1-Click Database & Hosting Installation Wizard
 * Automatically sets up MySQL database, runs migrations, seeds VIP keys, and writes api/config.php
 */

header('Content-Type: text/html; charset=UTF-8');

$configFile = __DIR__ . '/api/config.php';
$schemaFile = __DIR__ . '/database.sql';

// Check if already configured
$existingHost = 'localhost';
$existingDb = 'frking_db';
$existingUser = 'root';
$existingPass = '';
$existingPort = '3306';
$existingAdminPass = 'FRK@2026#WaqasKing!Secure9x';
$existingMasterKey = 'FRK-PRO-MASTER-KEY-2026';

if (file_exists($configFile)) {
    $content = @file_get_contents($configFile);
    if (preg_match("/define\('DB_HOST',\s*getenv\('DB_HOST'\)\s*\?:\s*'([^']*)'\)/", $content, $m)) $existingHost = $m[1];
    if (preg_match("/define\('DB_NAME',\s*getenv\('DB_NAME'\)\s*\?:\s*'([^']*)'\)/", $content, $m)) $existingDb = $m[1];
    if (preg_match("/define\('DB_USER',\s*getenv\('DB_USER'\)\s*\?:\s*'([^']*)'\)/", $content, $m)) $existingUser = $m[1];
    if (preg_match("/define\('DB_PASS',\s*getenv\('DB_PASS'\)\s*\?:\s*'([^']*)'\)/", $content, $m)) $existingPass = $m[1];
    if (preg_match("/define\('DB_PORT',\s*getenv\('DB_PORT'\)\s*\?:\s*([0-9]+)\)/", $content, $m)) $existingPort = $m[1];
    if (preg_match("/define\('ADMIN_PASSWORD',\s*getenv\('ADMIN_PASSWORD'\)\s*\?:\s*'([^']*)'\)/", $content, $m)) $existingAdminPass = $m[1];
    if (preg_match("/define\('MASTER_API_KEY',\s*getenv\('API_KEY'\)\s*\?:\s*'([^']*)'\)/", $content, $m)) $existingMasterKey = $m[1];
}

$status = null;
$message = '';
$logs = [];

// Handle Installation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = trim($_POST['db_pass'] ?? '');
    $dbPort = (int)($_POST['db_port'] ?? 3306);
    $adminPass = trim($_POST['admin_password'] ?? 'FRK@2026#WaqasKing!Secure9x');
    $masterKey = trim($_POST['master_key'] ?? 'FRK-PRO-MASTER-KEY-2026');

    if (empty($dbName) || empty($dbUser)) {
        $status = 'error';
        $message = 'Database Name and Database User are required.';
    } else {
        try {
            $logs[] = "Connecting to MySQL server at {$dbHost}:{$dbPort}...";
            
            // Try connecting to the specific database, or connect to server and create DB
            $pdo = null;
            try {
                $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
                $pdo = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
                $logs[] = "Connected successfully to database '{$dbName}'.";
            } catch (PDOException $e) {
                // If database doesn't exist, connect to MySQL server and create it
                $serverDsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
                $serverPdo = new PDO($serverDsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
                $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                $logs[] = "Created database '{$dbName}'.";

                $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
                $pdo = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
                $logs[] = "Connected to newly created database '{$dbName}'.";
            }

            // Create Tables
            $logs[] = "Creating table 'licenses'...";
            $pdo->exec("CREATE TABLE IF NOT EXISTS `licenses` (
                `id` VARCHAR(64) PRIMARY KEY,
                `license_key` VARCHAR(64) NOT NULL UNIQUE,
                `full_name` VARCHAR(128) NOT NULL,
                `phone` VARCHAR(64) DEFAULT '',
                `email` VARCHAR(128) DEFAULT '',
                `amount` VARCHAR(64) DEFAULT 'Paid',
                `status` ENUM('pending', 'approved', 'blocked') NOT NULL DEFAULT 'pending',
                `duration_label` VARCHAR(64) NOT NULL DEFAULT '1 Month',
                `expires_at` DATETIME DEFAULT NULL,
                `lifetime` TINYINT(1) NOT NULL DEFAULT 0,
                `max_devices` INT NOT NULL DEFAULT 1,
                `license_devices` TEXT DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_license_key` (`license_key`),
                INDEX `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $logs[] = "Creating table 'signals'...";
            $pdo->exec("CREATE TABLE IF NOT EXISTS `signals` (
                `id` VARCHAR(64) PRIMARY KEY,
                `pair` VARCHAR(32) NOT NULL,
                `market` VARCHAR(32) NOT NULL DEFAULT 'Binary Options',
                `timer` VARCHAR(16) NOT NULL DEFAULT '1M',
                `mode` VARCHAR(16) NOT NULL DEFAULT 'live',
                `direction` ENUM('BUY', 'SELL') NOT NULL,
                `entry` VARCHAR(32) NOT NULL,
                `expiry` VARCHAR(32) DEFAULT '',
                `accuracy` VARCHAR(16) DEFAULT '96%',
                `strength` VARCHAR(16) DEFAULT 'Ultra',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $logs[] = "Creating table 'app_config'...";
            $pdo->exec("CREATE TABLE IF NOT EXISTS `app_config` (
                `setting_key` VARCHAR(64) PRIMARY KEY,
                `setting_value` TEXT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Seed VIP Licenses
            $logs[] = "Seeding VIP pre-approved licenses...";
            $seeds = [
                ['lic_king_crown_001', 'FRK-VIP-LIFETIME-KING', 'Waqas King (VIP Master)', '+966554573612', 'codewithwaqas.dev@gmail.com', 'VIP Crown Sponsor', 'approved', 'Lifetime', null, 1, 10, '[]'],
                ['lic_master_001', $masterKey, 'VIP Master Admin', '+966554573612', 'emailwaqas7@gmail.com', 'VIP Master Key', 'approved', 'Lifetime', null, 1, 10, '[]'],
                ['lic_1year_crown_8820', 'FRK-CROWN-1YEAR-8820', 'Elite Crown Trader', '+966554573612', 'client@frkings.pro', '350 USD', 'approved', '1 Year', date('Y-m-d H:i:s', strtotime('+1 year')), 0, 5, '[]'],
                ['lic_elite_6m_5531', 'FRK-ELITE-6M-5531', 'Sultan Elite Pro', '+966554573612', 'sultan.trader@gmail.com', '180 USD', 'approved', '6 Months', date('Y-m-d H:i:s', strtotime('+6 months')), 0, 3, '[]'],
                ['lic_pro_3m_4419', 'FRK-PRO-3M-4419', 'Waqas Pro Trader', '+966554573612', 'codewithwaqas.dev@gmail.com', '100 USD', 'approved', '3 Months', date('Y-m-d H:i:s', strtotime('+3 months')), 0, 3, '[]'],
                ['lic_gold_1m_7720', 'FRK-GOLD-1M-7720', 'Quotex Gold Trader', '+966554573612', 'gold.trader@gmail.com', '50 USD', 'approved', '1 Month', date('Y-m-d H:i:s', strtotime('+1 month')), 0, 2, '[]'],
                ['lic_alpha_1m_9932', 'FRK-ALPHA-1M-9932', 'Alpha Scalper Max', '+966554573612', 'alpha.scalper@gmail.com', '50 USD', 'approved', '1 Month', date('Y-m-d H:i:s', strtotime('+1 month')), 0, 2, '[]'],
                ['lic_trial_3days_01', 'FRK-TRIAL-3DAYS-01', 'VIP 3-Day Trial User', '+966554573612', 'trial.user@gmail.com', 'Free Trial', 'approved', '3 Days', date('Y-m-d H:i:s', strtotime('+3 days')), 0, 1, '[]']
            ];

            $stmt = $pdo->prepare("INSERT INTO `licenses` 
                (`id`, `license_key`, `full_name`, `phone`, `email`, `amount`, `status`, `duration_label`, `expires_at`, `lifetime`, `max_devices`, `license_devices`, `created_at`, `updated_at`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE `status` = VALUES(`status`), `updated_at` = NOW()");

            foreach ($seeds as $s) {
                $stmt->execute($s);
            }

            // Save App Config in MySQL
            $configStmt = $pdo->prepare("INSERT INTO `app_config` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
            $configStmt->execute(['apiKey', $masterKey]);
            $configStmt->execute(['allowMasterApiKeyBypass', '1']);

            // Write api/config.php file
            $newConfigContent = "<?php\n" .
                "/**\n * FR-KING AURA — Generated by install.php\n */\n\n" .
                "define('DB_HOST', getenv('DB_HOST') ?: '{$dbHost}');\n" .
                "define('DB_NAME', getenv('DB_NAME') ?: '{$dbName}');\n" .
                "define('DB_USER', getenv('DB_USER') ?: '{$dbUser}');\n" .
                "define('DB_PASS', getenv('DB_PASS') ?: '{$dbPass}');\n" .
                "define('DB_PORT', getenv('DB_PORT') ?: {$dbPort});\n\n" .
                "define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: '{$adminPass}');\n" .
                "define('MASTER_API_KEY', getenv('API_KEY') ?: '{$masterKey}');\n" .
                "define('ALLOW_MASTER_KEY_BYPASS', true);\n\n" .
                "define('RECOVERY_EMAIL', 'emailwaqas7@gmail.com');\n" .
                "define('WHATSAPP_URL', 'https://chat.whatsapp.com/G1yKmNVIntiLx7c9KX5zEu');\n" .
                "define('TELEGRAM_URL', 'https://t.me/frking00');\n" .
                "define('PHONE', '+966554573612');\n" .
                "define('DEVELOPER', '@codetrader');\n";

            $writeOk = @file_put_contents($configFile, $newConfigContent);
            if ($writeOk !== false) {
                $logs[] = "Successfully written database configuration to 'api/config.php'.";
            } else {
                $logs[] = "Notice: Could not write directly to 'api/config.php' (check directory permissions 755).";
            }

            $status = 'success';
            $message = 'FR-KING AURA installed and connected successfully to your hosting MySQL database!';
        } catch (Exception $e) {
            $status = 'error';
            $message = 'MySQL Database Connection Error: ' . $e->getMessage();
            $logs[] = "Error details: " . $e->getMessage();
        }
    }
}

// System checks
$phpVersion = phpversion();
$hasPdo = extension_loaded('pdo');
$hasPdoMysql = extension_loaded('pdo_mysql');
$isConfigWritable = is_writable(dirname($configFile)) || (file_exists($configFile) && is_writable($configFile));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FR-KING AURA — Hosting Server Database Installer</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600;700&family=Inter:wght@400;600;700;800&family=Oxanium:wght@600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #050505;
      --card: #111111;
      --border: #34302A;
      --gold: #FFD000;
      --gold-dark: #2A2208;
      --success: #00D68F;
      --danger: #FF4D6D;
      --text: #FFFFFF;
      --text-muted: #A0A0A0;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: radial-gradient(ellipse at 50% 0%, rgba(255, 208, 0, 0.08), transparent 70%), var(--bg);
      color: var(--text);
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }
    .install-box {
      width: 100%;
      max-width: 580px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 32px;
      box-shadow: 0 16px 40px rgba(0,0,0,0.8), 0 0 30px rgba(255,208,0,0.12);
    }
    .brand-header {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 24px;
      padding-bottom: 20px;
      border-bottom: 1px solid var(--border);
    }
    .logo-img {
      width: 54px;
      height: 54px;
      border-radius: 14px;
      border: 1px solid var(--gold);
      box-shadow: 0 0 15px rgba(255,208,0,0.25);
      object-fit: cover;
    }
    h1 {
      font-family: 'Oxanium', sans-serif;
      font-size: 22px;
      font-weight: 800;
      letter-spacing: 0.5px;
    }
    .subtitle {
      color: var(--gold);
      font-size: 11px;
      font-family: 'IBM Plex Mono', monospace;
      font-weight: 600;
      letter-spacing: 1px;
    }
    .badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 10px;
      font-family: 'IBM Plex Mono', monospace;
      font-weight: 700;
    }
    .badge-ok { background: rgba(0, 214, 143, 0.15); color: var(--success); border: 1px solid var(--success); }
    .badge-warn { background: rgba(255, 77, 109, 0.15); color: var(--danger); border: 1px solid var(--danger); }
    .checks {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
      margin-bottom: 24px;
    }
    .check-item {
      background: #080808;
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 10px;
      text-align: center;
      font-size: 11px;
    }
    .check-item strong { display: block; font-family: 'IBM Plex Mono', monospace; margin-bottom: 4px; }
    .form-group { margin-bottom: 14px; }
    label {
      display: block;
      font-size: 11px;
      font-family: 'IBM Plex Mono', monospace;
      color: var(--text-muted);
      margin-bottom: 6px;
      font-weight: 600;
    }
    input {
      width: 100%;
      background: #080808;
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 10px 14px;
      color: #FFF;
      font-size: 13px;
      font-family: 'IBM Plex Mono', monospace;
      transition: all 0.2s;
    }
    input:focus {
      outline: none;
      border-color: var(--gold);
      box-shadow: 0 0 10px rgba(255,208,0,0.25);
    }
    .row { display: flex; gap: 12px; }
    .row .form-group { flex: 1; }
    .btn-submit {
      width: 100%;
      background: linear-gradient(135deg, #FFD000 0%, #D9AD00 100%);
      color: #050505;
      font-family: 'Oxanium', sans-serif;
      font-size: 14px;
      font-weight: 800;
      padding: 13px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      box-shadow: 0 0 20px rgba(255,208,0,0.35);
      margin-top: 10px;
      transition: transform 0.1s, filter 0.2s;
    }
    .btn-submit:hover { filter: brightness(1.1); }
    .btn-submit:active { transform: scale(0.99); }
    .alert {
      padding: 14px;
      border-radius: 10px;
      font-size: 12px;
      margin-bottom: 20px;
      line-height: 1.5;
    }
    .alert-success { background: rgba(0, 214, 143, 0.12); border: 1px solid var(--success); color: var(--success); }
    .alert-error { background: rgba(255, 77, 109, 0.12); border: 1px solid var(--danger); color: var(--danger); }
    .log-box {
      background: #000;
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 10px;
      max-height: 140px;
      overflow-y: auto;
      font-family: 'IBM Plex Mono', monospace;
      font-size: 10px;
      color: #00D68F;
      margin-top: 15px;
    }
    .action-links {
      display: flex;
      gap: 12px;
      margin-top: 20px;
    }
    .action-links a {
      flex: 1;
      text-align: center;
      padding: 10px;
      border-radius: 8px;
      text-decoration: none;
      font-family: 'Oxanium', sans-serif;
      font-weight: 700;
      font-size: 12px;
      transition: all 0.2s;
    }
    .btn-primary { background: var(--gold); color: #000; }
    .btn-secondary { background: #202020; color: #FFF; border: 1px solid var(--border); }
  </style>
</head>
<body>
  <div class="install-box">
    <div class="brand-header">
      <img src="fr-kings-logo.jpg" class="logo-img" onerror="this.src='icon.svg'" alt="FR-KINGs">
      <div>
        <h1>FR-KING AURA</h1>
        <div class="subtitle">HOSTING SERVER DATABASE INSTALLER</div>
      </div>
    </div>

    <!-- System Requirements Checks -->
    <div class="checks">
      <div class="check-item">
        <strong>PHP <?= substr($phpVersion, 0, 5) ?></strong>
        <span class="badge badge-ok">Compatible</span>
      </div>
      <div class="check-item">
        <strong>PDO MySQL</strong>
        <span class="badge <?= $hasPdoMysql ? 'badge-ok' : 'badge-warn' ?>">
          <?= $hasPdoMysql ? 'Installed' : 'Missing' ?>
        </span>
      </div>
      <div class="check-item">
        <strong>Config Writable</strong>
        <span class="badge <?= $isConfigWritable ? 'badge-ok' : 'badge-warn' ?>">
          <?= $isConfigWritable ? 'Ready' : '755 Needed' ?>
        </span>
      </div>
    </div>

    <?php if ($status === 'success'): ?>
      <div class="alert alert-success">
        <strong>🎉 Installation Successful!</strong><br>
        <?= htmlspecialchars($message) ?><br>
        Database tables created and VIP keys seeded into MySQL.
      </div>

      <div class="action-links">
        <a href="./" class="btn-primary">LAUNCH TRADING TERMINAL</a>
        <a href="./admin" class="btn-secondary">OPEN ADMIN PANEL</a>
      </div>

      <?php if (!empty($logs)): ?>
        <div class="log-box">
          <?php foreach ($logs as $l): ?>
            <div>[✓] <?= htmlspecialchars($l) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <?php if ($status === 'error'): ?>
        <div class="alert alert-error">
          <strong>Installation Failed</strong><br>
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="row">
          <div class="form-group" style="flex: 2;">
            <label>MYSQL DATABASE HOST</label>
            <input type="text" name="db_host" value="<?= htmlspecialchars($existingHost) ?>" placeholder="localhost" required>
          </div>
          <div class="form-group" style="flex: 1;">
            <label>PORT</label>
            <input type="number" name="db_port" value="<?= htmlspecialchars($existingPort) ?>" placeholder="3306" required>
          </div>
        </div>

        <div class="form-group">
          <label>DATABASE NAME</label>
          <input type="text" name="db_name" value="<?= htmlspecialchars($existingDb) ?>" placeholder="e.g. cpaneluser_frking" required>
        </div>

        <div class="form-group">
          <label>DATABASE USERNAME</label>
          <input type="text" name="db_user" value="<?= htmlspecialchars($existingUser) ?>" placeholder="e.g. cpaneluser_admin" required>
        </div>

        <div class="form-group">
          <label>DATABASE PASSWORD</label>
          <input type="password" name="db_pass" value="<?= htmlspecialchars($existingPass) ?>" placeholder="MySQL User Password">
        </div>

        <div class="row">
          <div class="form-group">
            <label>ADMIN PANEL PASSWORD</label>
            <input type="text" name="admin_password" value="<?= htmlspecialchars($existingAdminPass) ?>" required>
          </div>
          <div class="form-group">
            <label>MASTER VIP KEY</label>
            <input type="text" name="master_key" value="<?= htmlspecialchars($existingMasterKey) ?>" required>
          </div>
        </div>

        <button type="submit" class="btn-submit">
          TEST CONNECTION & INSTALL DATABASE
        </button>
      </form>

      <?php if (!empty($logs)): ?>
        <div class="log-box">
          <?php foreach ($logs as $l): ?>
            <div><?= htmlspecialchars($l) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>

<?php
/**
 * FR-KING AURA — Hosting Server Database Connection & Data Access Layer
 * Supports MySQL (PDO) on the hosting server, with fallback to JSON storage.
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $pdo = null;
    private static $useFallbackJson = false;
    private static $jsonFilePath = null;

    public static function init() {
        self::$jsonFilePath = dirname(__DIR__) . '/data/frking.db.json';

        if (self::$pdo !== null) {
            return self::$pdo;
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 3,
            ]);

            self::ensureTables();
            return self::$pdo;
        } catch (Exception $e) {
            // If MySQL is not reachable or database not created yet, use local JSON storage fallback
            self::$useFallbackJson = true;
            return null;
        }
    }

    private static function ensureTables() {
        if (!self::$pdo) return;

        // 1. Licenses Table
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS `licenses` (
            `id` VARCHAR(64) PRIMARY KEY,
            `license_key` VARCHAR(64) NOT NULL UNIQUE,
            `full_name` VARCHAR(128) NOT NULL,
            `phone` VARCHAR(64) DEFAULT '',
            `email` VARCHAR(128) DEFAULT '',
            `amount` VARCHAR(64) DEFAULT 'Paid',
            `status` ENUM('pending', 'approved', 'blocked') DEFAULT 'pending',
            `duration_label` VARCHAR(64) DEFAULT '1 Month',
            `expires_at` DATETIME NULL,
            `lifetime` TINYINT(1) DEFAULT 0,
            `max_devices` INT DEFAULT 1,
            `license_devices` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_license_key` (`license_key`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 2. Signals Table
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS `signals` (
            `id` VARCHAR(64) PRIMARY KEY,
            `pair` VARCHAR(32) NOT NULL,
            `market` VARCHAR(32) DEFAULT 'Binary Options',
            `timer` VARCHAR(16) DEFAULT '1M',
            `mode` VARCHAR(16) DEFAULT 'live',
            `direction` ENUM('BUY', 'SELL') NOT NULL,
            `entry` VARCHAR(32) NOT NULL,
            `expiry` VARCHAR(32) DEFAULT '',
            `accuracy` VARCHAR(16) DEFAULT '96%',
            `strength` VARCHAR(16) DEFAULT 'Ultra',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 3. App Config Table
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS `app_config` (
            `setting_key` VARCHAR(64) PRIMARY KEY,
            `setting_value` TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Seed default licenses if table is empty
        $count = self::$pdo->query("SELECT COUNT(*) FROM `licenses`")->fetchColumn();
        if ($count == 0) {
            self::seedDefaultLicenses();
        }
    }

    private static function seedDefaultLicenses() {
        $seeds = [
            [
                'id' => 'lic_king_crown_001',
                'license_key' => 'FRK-VIP-LIFETIME-KING',
                'full_name' => 'Waqas King (VIP Master)',
                'phone' => '+966554573612',
                'email' => 'codewithwaqas.dev@gmail.com',
                'amount' => 'VIP Crown Sponsor',
                'status' => 'approved',
                'duration_label' => 'Lifetime',
                'expires_at' => null,
                'lifetime' => 1,
                'max_devices' => 10,
                'license_devices' => json_encode([]),
            ],
            [
                'id' => 'lic_master_001',
                'license_key' => 'FRK-PRO-MASTER-KEY-2026',
                'full_name' => 'VIP Master Admin',
                'phone' => '+966554573612',
                'email' => 'emailwaqas7@gmail.com',
                'amount' => 'VIP Master Key',
                'status' => 'approved',
                'duration_label' => 'Lifetime',
                'expires_at' => null,
                'lifetime' => 1,
                'max_devices' => 10,
                'license_devices' => json_encode([]),
            ],
            [
                'id' => 'lic_1year_crown_8820',
                'license_key' => 'FRK-CROWN-1YEAR-8820',
                'full_name' => 'Elite Crown Trader',
                'phone' => '+966554573612',
                'email' => 'client@frkings.pro',
                'amount' => '350 USD',
                'status' => 'approved',
                'duration_label' => '1 Year',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
                'lifetime' => 0,
                'max_devices' => 5,
                'license_devices' => json_encode([]),
            ],
            [
                'id' => 'lic_elite_6m_5531',
                'license_key' => 'FRK-ELITE-6M-5531',
                'full_name' => 'Sultan Elite Pro',
                'phone' => '+966554573612',
                'email' => 'sultan.trader@gmail.com',
                'amount' => '180 USD',
                'status' => 'approved',
                'duration_label' => '6 Months',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+6 months')),
                'lifetime' => 0,
                'max_devices' => 3,
                'license_devices' => json_encode([]),
            ],
            [
                'id' => 'lic_pro_3m_4419',
                'license_key' => 'FRK-PRO-3M-4419',
                'full_name' => 'Waqas Pro Trader',
                'phone' => '+966554573612',
                'email' => 'codewithwaqas.dev@gmail.com',
                'amount' => '100 USD',
                'status' => 'approved',
                'duration_label' => '3 Months',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+3 months')),
                'lifetime' => 0,
                'max_devices' => 3,
                'license_devices' => json_encode([]),
            ]
        ];

        $stmt = self::$pdo->prepare("INSERT INTO `licenses` 
            (`id`, `license_key`, `full_name`, `phone`, `email`, `amount`, `status`, `duration_label`, `expires_at`, `lifetime`, `max_devices`, `license_devices`, `created_at`, `updated_at`)
            VALUES (:id, :license_key, :full_name, :phone, :email, :amount, :status, :duration_label, :expires_at, :lifetime, :max_devices, :license_devices, NOW(), NOW())");

        foreach ($seeds as $lic) {
            $stmt->execute($lic);
        }
    }

    // JSON Fallback helpers
    private static function loadJson() {
        if (!file_exists(self::$jsonFilePath)) {
            $dir = dirname(self::$jsonFilePath);
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            file_put_contents(self::$jsonFilePath, json_encode([
                'config' => [],
                'licenses' => [],
                'signals' => []
            ]));
        }
        $raw = @file_get_contents(self::$jsonFilePath);
        $data = json_decode($raw, true);
        return is_array($data) ? $data : ['config' => [], 'licenses' => [], 'signals' => []];
    }

    private static function saveJson($data) {
        @file_put_contents(self::$jsonFilePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    // --- Public API Methods ---

    public static function getConfig() {
        self::init();
        if (self::$pdo) {
            $rows = self::$pdo->query("SELECT setting_key, setting_value FROM app_config")->fetchAll();
            $conf = [];
            foreach ($rows as $r) {
                $conf[$r['setting_key']] = $r['setting_value'];
            }
            return array_merge([
                'apiKey' => MASTER_API_KEY,
                'recoveryEmail' => RECOVERY_EMAIL,
                'supabaseUrl' => '',
                'supabaseAnonKey' => '',
                'authMode' => 'local',
                'allowMasterApiKeyBypass' => ALLOW_MASTER_KEY_BYPASS,
                'whatsappUrl' => WHATSAPP_URL,
                'telegramUrl' => TELEGRAM_URL,
                'phone' => PHONE,
                'developer' => DEVELOPER,
                'databaseType' => 'Hosting MySQL (Active)',
            ], $conf);
        }

        $json = self::loadJson();
        return array_merge([
            'apiKey' => MASTER_API_KEY,
            'recoveryEmail' => RECOVERY_EMAIL,
            'supabaseUrl' => '',
            'supabaseAnonKey' => '',
            'authMode' => 'local',
            'allowMasterApiKeyBypass' => ALLOW_MASTER_KEY_BYPASS,
            'whatsappUrl' => WHATSAPP_URL,
            'telegramUrl' => TELEGRAM_URL,
            'phone' => PHONE,
            'developer' => DEVELOPER,
            'databaseType' => 'JSON Fallback',
        ], $json['config'] ?? []);
    }

    public static function updateConfig($updates) {
        self::init();
        if (self::$pdo) {
            $stmt = self::$pdo->prepare("INSERT INTO app_config (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = :v2");
            foreach ($updates as $k => $v) {
                $valStr = is_bool($v) ? ($v ? '1' : '0') : (string)$v;
                $stmt->execute([':k' => $k, ':v' => $valStr, ':v2' => $valStr]);
            }
            return self::getConfig();
        }

        $json = self::loadJson();
        $json['config'] = array_merge($json['config'] ?? [], $updates);
        self::saveJson($json);
        return self::getConfig();
    }

    public static function getLicenses() {
        self::init();
        if (self::$pdo) {
            $rows = self::$pdo->query("SELECT * FROM licenses ORDER BY created_at DESC")->fetchAll();
            return array_map(function($item) {
                $item['lifetime'] = (bool)$item['lifetime'];
                $item['max_devices'] = (int)$item['max_devices'];
                $item['license_devices'] = json_decode($item['license_devices'] ?? '[]', true) ?: [];
                return $item;
            }, $rows);
        }

        $json = self::loadJson();
        return $json['licenses'] ?? [];
    }

    public static function findLicenseByKey($key) {
        $clean = strtoupper(trim($key));
        self::init();
        if (self::$pdo) {
            $stmt = self::$pdo->prepare("SELECT * FROM licenses WHERE UPPER(license_key) = :k LIMIT 1");
            $stmt->execute([':k' => $clean]);
            $row = $stmt->fetch();
            if ($row) {
                $row['lifetime'] = (bool)$row['lifetime'];
                $row['max_devices'] = (int)$row['max_devices'];
                $row['license_devices'] = json_decode($row['license_devices'] ?? '[]', true) ?: [];
                return $row;
            }
            return null;
        }

        $licenses = self::getLicenses();
        foreach ($licenses as $lic) {
            if (strtoupper(trim($lic['license_key'])) === $clean) {
                return $lic;
            }
        }
        return null;
    }

    public static function createLicense($input) {
        self::init();
        $rawKey = !empty($input['custom_key']) ? strtoupper(trim($input['custom_key'])) : null;
        if (!$rawKey) {
            $rawKey = 'FRK-' . rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999);
        }

        $duration = $input['duration'] ?? '1 Month';
        $lifetime = ($duration === 'Lifetime') ? 1 : 0;
        $expiresAt = null;

        if (!$lifetime) {
            $map = [
                '1 Day' => '+1 day',
                '2 Days' => '+2 days',
                '3 Days' => '+3 days',
                '5 Days' => '+5 days',
                '1 Week' => '+1 week',
                '15 Days' => '+15 days',
                '1 Month' => '+1 month',
                '3 Months' => '+3 months',
                '6 Months' => '+6 months',
                '1 Year' => '+1 year',
            ];
            $str = $map[$duration] ?? '+1 month';
            $expiresAt = date('Y-m-d H:i:s', strtotime($str));
        }

        $newLic = [
            'id' => 'lic_' . time() . '_' . substr(md5(uniqid()), 0, 5),
            'license_key' => $rawKey,
            'full_name' => trim($input['full_name'] ?? 'Client'),
            'phone' => trim($input['phone'] ?? ''),
            'email' => trim($input['email'] ?? ''),
            'amount' => trim($input['amount'] ?? 'Paid'),
            'status' => $input['status'] ?? 'pending',
            'duration_label' => $duration,
            'expires_at' => $expiresAt,
            'lifetime' => $lifetime,
            'max_devices' => (int)($input['max_devices'] ?? 1),
            'license_devices' => json_encode([]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (self::$pdo) {
            $stmt = self::$pdo->prepare("INSERT INTO licenses 
                (id, license_key, full_name, phone, email, amount, status, duration_label, expires_at, lifetime, max_devices, license_devices, created_at, updated_at)
                VALUES (:id, :license_key, :full_name, :phone, :email, :amount, :status, :duration_label, :expires_at, :lifetime, :max_devices, :license_devices, :created_at, :updated_at)");
            $stmt->execute($newLic);
            $newLic['lifetime'] = (bool)$newLic['lifetime'];
            $newLic['license_devices'] = [];
            return $newLic;
        }

        $json = self::loadJson();
        $newLic['lifetime'] = (bool)$newLic['lifetime'];
        $newLic['license_devices'] = [];
        array_unshift($json['licenses'], $newLic);
        self::saveJson($json);
        return $newLic;
    }

    public static function bindDevice($key, $deviceId) {
        $lic = self::findLicenseByKey($key);
        if (!$lic) {
            return ['ok' => false, 'error' => 'License key not found.'];
        }

        if ($lic['status'] === 'blocked') {
            return ['ok' => false, 'error' => 'This license has been blocked by administrator.'];
        }

        if ($lic['status'] === 'pending') {
            return ['ok' => false, 'error' => 'Payment verification pending. Please wait for admin approval.'];
        }

        if (!$lic['lifetime'] && !empty($lic['expires_at']) && strtotime($lic['expires_at']) < time()) {
            return ['ok' => false, 'error' => 'License key has expired. Please contact admin to renew.'];
        }

        $devices = $lic['license_devices'];
        $cleanDevice = trim($deviceId);
        $found = false;
        foreach ($devices as $d) {
            if (strcasecmp($d, $cleanDevice) === 0) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            if (count($devices) >= $lic['max_devices']) {
                return [
                    'ok' => false,
                    'error' => 'Device limit reached (' . count($devices) . '/' . $lic['max_devices'] . ' bound). Contact admin to reset devices.'
                ];
            }
            $devices[] = $cleanDevice;
            $lic['license_devices'] = $devices;

            self::init();
            if (self::$pdo) {
                $stmt = self::$pdo->prepare("UPDATE licenses SET license_devices = :dev, updated_at = NOW() WHERE id = :id");
                $stmt->execute([':dev' => json_encode($devices), ':id' => $lic['id']]);
            } else {
                $json = self::loadJson();
                foreach ($json['licenses'] as &$l) {
                    if ($l['id'] === $lic['id'] || $l['license_key'] === $lic['license_key']) {
                        $l['license_devices'] = $devices;
                        $l['updated_at'] = date('Y-m-d H:i:s');
                        break;
                    }
                }
                self::saveJson($json);
            }
        }

        return ['ok' => true, 'license' => $lic];
    }

    public static function updateLicenseAction($id, $action, $duration = null, $maxDevices = null, $deviceId = null) {
        self::init();

        if ($action === 'delete') {
            if (self::$pdo) {
                $stmt = self::$pdo->prepare("DELETE FROM licenses WHERE id = :id OR license_key = :k");
                $stmt->execute([':id' => $id, ':k' => $id]);
            } else {
                $json = self::loadJson();
                $json['licenses'] = array_values(array_filter($json['licenses'], function($l) use ($id) {
                    return $l['id'] !== $id && $l['license_key'] !== $id;
                }));
                self::saveJson($json);
            }
            return ['ok' => true];
        }

        $lic = self::findLicenseByKey($id);
        if (!$lic) {
            // Try find by id
            $all = self::getLicenses();
            foreach ($all as $l) {
                if ($l['id'] === $id) { $lic = $l; break; }
            }
        }

        if (!$lic) {
            return ['ok' => false, 'error' => 'License not found.'];
        }

        if ($action === 'approve') {
            $lic['status'] = 'approved';
            if ($duration) {
                $lic['duration_label'] = $duration;
                $lifetime = ($duration === 'Lifetime') ? 1 : 0;
                $lic['lifetime'] = (bool)$lifetime;
                if ($lifetime) {
                    $lic['expires_at'] = null;
                } else {
                    $map = [
                        '1 Day' => '+1 day', '2 Days' => '+2 days', '3 Days' => '+3 days',
                        '5 Days' => '+5 days', '1 Week' => '+1 week', '15 Days' => '+15 days',
                        '1 Month' => '+1 month', '3 Months' => '+3 months', '6 Months' => '+6 months', '1 Year' => '+1 year',
                    ];
                    $lic['expires_at'] = date('Y-m-d H:i:s', strtotime($map[$duration] ?? '+1 month'));
                }
            }
            if ($maxDevices !== null && $maxDevices > 0) {
                $lic['max_devices'] = (int)$maxDevices;
            }
        } elseif ($action === 'block') {
            $lic['status'] = 'blocked';
        } elseif ($action === 'reset_devices') {
            $lic['license_devices'] = [];
        } elseif ($action === 'remove_device' && $deviceId) {
            $lic['license_devices'] = array_values(array_filter($lic['license_devices'], function($d) use ($deviceId) {
                return strcasecmp(trim($d), trim($deviceId)) !== 0;
            }));
        }

        if (self::$pdo) {
            $stmt = self::$pdo->prepare("UPDATE licenses SET 
                status = :status, 
                duration_label = :duration_label, 
                expires_at = :expires_at, 
                lifetime = :lifetime, 
                max_devices = :max_devices, 
                license_devices = :devices, 
                updated_at = NOW() 
                WHERE id = :id");
            $stmt->execute([
                ':status' => $lic['status'],
                ':duration_label' => $lic['duration_label'],
                ':expires_at' => $lic['expires_at'],
                ':lifetime' => $lic['lifetime'] ? 1 : 0,
                ':max_devices' => $lic['max_devices'],
                ':devices' => json_encode($lic['license_devices']),
                ':id' => $lic['id'],
            ]);
        } else {
            $json = self::loadJson();
            foreach ($json['licenses'] as &$l) {
                if ($l['id'] === $lic['id'] || $l['license_key'] === $lic['license_key']) {
                    $l = array_merge($l, $lic);
                    break;
                }
            }
            self::saveJson($json);
        }

        return ['ok' => true, 'license' => $lic];
    }

    public static function getSignals($limit = 50) {
        self::init();
        if (self::$pdo) {
            $stmt = self::$pdo->prepare("SELECT * FROM signals ORDER BY created_at DESC LIMIT :lim");
            $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $json = self::loadJson();
        return array_slice($json['signals'] ?? [], 0, $limit);
    }

    public static function addSignal($sig) {
        self::init();
        $record = [
            'id' => $sig['id'] ?? ('sig_' . time()),
            'pair' => $sig['pair'] ?? '',
            'market' => $sig['market'] ?? 'Binary Options',
            'timer' => $sig['timer'] ?? '1M',
            'mode' => $sig['mode'] ?? 'live',
            'direction' => $sig['direction'] ?? 'BUY',
            'entry' => $sig['entry'] ?? '',
            'expiry' => $sig['expiry'] ?? '',
            'accuracy' => $sig['accuracy'] ?? '96%',
            'strength' => $sig['strength'] ?? 'Ultra',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if (self::$pdo) {
            $stmt = self::$pdo->prepare("INSERT INTO signals 
                (id, pair, market, timer, mode, direction, entry, expiry, accuracy, strength, created_at)
                VALUES (:id, :pair, :market, :timer, :mode, :direction, :entry, :expiry, :accuracy, :strength, :created_at)");
            $stmt->execute($record);
            return $record;
        }

        $json = self::loadJson();
        array_unshift($json['signals'], $record);
        if (count($json['signals']) > 500) {
            $json['signals'] = array_slice($json['signals'], 0, 500);
        }
        self::saveJson($json);
        return $record;
    }
}

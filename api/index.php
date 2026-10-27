<?php
/**
 * FR-KING AURA — Main PHP Backend Router
 * Handles all API calls for the React Terminal frontend using the hosting server's MySQL database.
 */

// Enable error logging, disable HTML error output in API mode
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Required HTTP Headers for JSON and CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, apikey");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Parse incoming JSON body
$rawInput = file_get_contents('php://input');
$body = json_decode($rawInput, true) ?: [];

// Normalize Request URI (supports domain root or any subfolder deployment)
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (preg_match('#/api(?:/index\.php|/|\.php/)?(.*)$#i', $requestUri, $matches)) {
    $route = '/' . ltrim($matches[1], '/');
} else {
    $route = $requestUri;
}
$route = preg_replace('/\.php$/i', '', $route);
$route = '/' . trim($route, '/');
if ($route === '//' || $route === '') $route = '/';
$method = $_SERVER['REQUEST_METHOD'];

// Route Dispatcher
switch ($route) {
    // 1. Health Check
    case '/health':
    case '/':
        echo json_encode([
            'ok' => true,
            'service' => 'FR-KING AURA PHP Backend',
            'status' => 'online',
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => Database::init() ? 'MySQL Connected' : 'JSON Storage Active',
        ]);
        break;

    // 2. App Configuration
    case '/config':
        if ($method === 'GET') {
            $config = Database::getConfig();
            echo json_encode($config);
        } elseif ($method === 'POST') {
            $password = $body['password'] ?? '';
            if ($password !== ADMIN_PASSWORD) {
                http_response_code(401);
                echo json_encode(['ok' => false, 'error' => 'Unauthorized: Invalid admin password.']);
                exit();
            }

            $updates = [];
            if (isset($body['apiKey'])) $updates['apiKey'] = $body['apiKey'];
            if (isset($body['allowMasterApiKeyBypass'])) $updates['allowMasterApiKeyBypass'] = (bool)$body['allowMasterApiKeyBypass'];

            $newConfig = Database::updateConfig($updates);
            echo json_encode(['ok' => true, 'config' => $newConfig]);
        }
        break;

    // 3. Verify License Key
    case '/verify-key':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
            exit();
        }

        $key = trim($body['key'] ?? '');
        $device = trim($body['device'] ?? 'device-unknown');

        if (!$key) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'License key is required.']);
            exit();
        }

        $cleanKey = strtoupper($key);
        $config = Database::getConfig();

        // Check Master API Key bypass
        if (!empty($config['allowMasterApiKeyBypass']) && $cleanKey === strtoupper(MASTER_API_KEY)) {
            echo json_encode([
                'ok' => true,
                'license' => [
                    'id' => 'master_api_key',
                    'license_key' => $cleanKey,
                    'full_name' => 'VIP Master Admin',
                    'status' => 'approved',
                    'lifetime' => true,
                    'duration_label' => 'Master API Key',
                    'max_devices' => 999,
                    'license_devices' => [$device],
                    'created_at' => date('Y-m-d H:i:s'),
                ]
            ]);
            exit();
        }

        // Verify in database
        $bindResult = Database::bindDevice($cleanKey, $device);
        if (!$bindResult['ok']) {
            echo json_encode([
                'ok' => false,
                'error' => $bindResult['error'] ?? 'Device verification failed.'
            ]);
            exit();
        }

        echo json_encode([
            'ok' => true,
            'license' => $bindResult['license']
        ]);
        break;

    // 4. Register License Key
    case '/register-key':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
            exit();
        }

        $fullName = trim($body['full_name'] ?? '');
        $phone = trim($body['phone'] ?? '');
        $email = trim($body['email'] ?? '');
        $amount = trim($body['amount'] ?? 'Pending Payment');

        if (!$fullName || !$phone || !$email) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Full name, phone, and Gmail are required.']);
            exit();
        }

        try {
            $newLic = Database::createLicense([
                'full_name' => $fullName,
                'phone' => $phone,
                'email' => $email,
                'amount' => $amount,
                'duration' => '1 Month',
                'max_devices' => 1,
                'status' => 'pending',
            ]);

            echo json_encode([
                'ok' => true,
                'key' => $newLic['license_key'],
                'license' => $newLic
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;

    // 5. Admin Login
    case '/admin/login':
        $password = $body['password'] ?? '';
        if ($password === ADMIN_PASSWORD) {
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Invalid admin password.']);
        }
        break;

    // 6. Admin Get All Keys
    case '/admin/keys':
        $password = $body['password'] ?? '';
        if ($password !== ADMIN_PASSWORD) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Invalid admin credentials.']);
            exit();
        }

        $keys = Database::getLicenses();
        echo json_encode(['ok' => true, 'keys' => $keys]);
        break;

    // 7. Admin Action (Approve, Block, Delete, Reset Devices, Remove Device)
    case '/admin/action':
        $password = $body['password'] ?? '';
        if ($password !== ADMIN_PASSWORD) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Invalid admin credentials.']);
            exit();
        }

        $id = $body['id'] ?? '';
        $action = $body['action'] ?? '';
        $duration = $body['duration'] ?? null;
        $maxDevices = isset($body['max_devices']) ? (int)$body['max_devices'] : null;
        $deviceId = $body['deviceId'] ?? null;

        if (!$id || !$action) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'License ID and action are required.']);
            exit();
        }

        $result = Database::updateLicenseAction($id, $action, $duration, $maxDevices, $deviceId);
        echo json_encode($result);
        break;

    // 8. Admin Create Pre-Approved Key
    case '/admin/create-key':
        $password = $body['password'] ?? '';
        if ($password !== ADMIN_PASSWORD) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Invalid admin credentials.']);
            exit();
        }

        $newLic = Database::createLicense([
            'custom_key' => $body['custom_key'] ?? null,
            'full_name' => $body['full_name'] ?? 'VIP Client',
            'phone' => $body['phone'] ?? '+966554573612',
            'email' => $body['email'] ?? 'client@frkings.pro',
            'amount' => 'Admin Minted',
            'duration' => $body['duration'] ?? '1 Month',
            'max_devices' => isset($body['max_devices']) ? (int)$body['max_devices'] : 1,
            'status' => 'approved',
        ]);

        echo json_encode(['ok' => true, 'license' => $newLic]);
        break;

    // 9. Signals Endpoints
    case '/signals':
        if ($method === 'GET') {
            $signals = Database::getSignals();
            echo json_encode(['ok' => true, 'signals' => $signals]);
        } elseif ($method === 'POST') {
            $sig = $body['signal'] ?? null;
            if (!$sig || empty($sig['pair']) || empty($sig['direction'])) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Invalid signal payload.']);
                exit();
            }
            $saved = Database::addSignal($sig);
            echo json_encode(['ok' => true, 'signal' => $saved]);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Endpoint not found: ' . $route]);
        break;
}

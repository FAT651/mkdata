<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

use Binali\Models\User;
use Binali\Config\Database;
use PDO;
use PDOException;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;


require_once __DIR__ . '/../services/airtime.service.php';
require_once __DIR__ . '/../services/data.service.php';
require_once __DIR__ . '/../services/cable.service.php';
require_once __DIR__ . '/../services/electricity.service.php';
require_once __DIR__ . '/../services/exam.service.php';
require_once __DIR__ . '/../services/recharge.service.php';
require_once __DIR__ . '/../services/rechargepin.service.php';
require_once __DIR__ . '/../services/datapin.service.php';
require_once __DIR__ . '/../services/user.service.php';
require_once __DIR__ . '/../services/beneficiary.service.php';

// Database helper (used to resolve network names to IDs when client sends name)
require_once __DIR__ . '/../db/database.php';

/**
 * Resolve a network identifier provided by the client.
 * Accepts numeric IDs (returned as int) or network names (case-insensitive).
 * Returns integer network id or null when not found.
 */
function resolveNetworkIdFromInput($input) {
    if ($input === null) return null;
    // If numeric, assume it's already the network id
    if (is_numeric($input)) return (int)$input;

    // Otherwise try to lookup by name in the networkid table
    try {
        $db = new Database();
        $rows = $db->query("SELECT nId FROM networkid WHERE LOWER(network) = LOWER(?) LIMIT 1", [$input]);
        if (!empty($rows) && isset($rows[0]['nId'])) {
            return (int)$rows[0]['nId'];
        }
        // Try match against the networkid column as a fallback
        $rows = $db->query("SELECT nId FROM networkid WHERE networkid = ? LIMIT 1", [$input]);
        if (!empty($rows) && isset($rows[0]['nId'])) {
            return (int)$rows[0]['nId'];
        }
    } catch (Exception $e) {
        error_log('resolveNetworkIdFromInput error: ' . $e->getMessage());
    }
    return null;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Debug logging
error_log("Original Request URI: " . $uri);
error_log("Request Method: " . $requestMethod);

// Extract the endpoint from the URI
$uriParts = explode('/api/', $uri, 2);
$endpoint = isset($uriParts[1]) ? trim($uriParts[1], '/') : '';

// Debug logging
error_log("Extracted endpoint: " . $endpoint);

// Split for additional parameters if needed
$uriSegments = explode('/', $endpoint);

// Use the first segment as the endpoint
$endpoint = $uriSegments[0] ?? '';
$id = $uriSegments[1] ?? null;

// Debug logging
error_log("Processing endpoint: " . $endpoint);

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'Invalid endpoint',
    'data' => null
];

try {
    switch ($endpoint) {
        case 'delete-account':
            if ($requestMethod !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->userId) || !isset($data->reason)) {
                throw new Exception('Missing required parameters');
            }

            try {
                $user_service = new UserService();
                $response['data'] = $user_service->deleteAccount($data->userId, $data->reason);
                $response['status'] = 'success';
                $response['message'] = 'Account deleted successfully';
            } catch (PDOException $e) {
                error_log("Database error in delete-account: " . $e->getMessage());
                http_response_code(503);
                throw new Exception("Database service is currently unavailable. Please try again later.");
            } catch (Exception $e) {
                error_log("Error in delete-account: " . $e->getMessage());
                throw $e;
            }
            break;
    case 'airtime':
            if ($requestMethod !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $rawInput = file_get_contents("php://input");
            error_log("Raw input: " . $rawInput);

            $data = json_decode($rawInput);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON: ' . json_last_error_msg());
            }

            error_log("Decoded data: " . print_r($data, true));

            if (!isset($data->network) || !isset($data->phone) || !isset($data->amount) || !isset($data->user_id)) {
                throw new Exception('Missing required parameters: ' .
                    (!isset($data->network) ? 'network ' : '') .
                    (!isset($data->phone) ? 'phone ' : '') .
                    (!isset($data->amount) ? 'amount ' : '') .
                    (!isset($data->user_id) ? 'user_id' : ''));
            }

            // Resolve network: allow client to send either numeric id or network name
            $resolvedNetworkId = resolveNetworkIdFromInput($data->network);
            if ($resolvedNetworkId === null) {
                // still allow sending network name to the AirtimeService which can map names
                $networkForService = $data->network;
            } else {
                $networkForService = $resolvedNetworkId;
            }

            $service = new AirtimeService();
            // Let the service accept either numeric id or name; we pass resolved value when possible
            $svcResult = $service->purchaseAirtime($networkForService, $data->phone, $data->amount, $data->user_id);

            // Normalize and propagate to top-level response
            $response['data'] = $svcResult['data'] ?? $svcResult;
            $response['message'] = $svcResult['message'] ?? '';
            $response['status'] = $svcResult['status'] ?? 'failed';

            // Set HTTP response code based on service status
            if ($response['status'] === 'failed') {
                http_response_code(500);
            } else {
                // success or processing
                http_response_code(200);
            }
            break;

        case 'airtime-plans':
            $service = new AirtimeService();
            $response['data'] = $service->getAirtimePlans();
            $response['status'] = 'success';
            $response['message'] = 'Airtime plans fetched successfully';
            break;
        
        case 'network-status':
            // Fetch network statuses from networkid table
            try {
                $db = new Database();
                $query = "SELECT nId, networkid, network, networkStatus, vtuStatus, smeStatus, 
                         sme2Status, giftingStatus, corporateStatus, couponStatus, 
                         datapinStatus, airtimepinStatus, sharesellStatus
                  FROM networkid ORDER BY nId";
                $rows = $db->query($query);
                
                if (empty($rows)) {
                    $response['data'] = [];
                    $response['status'] = 'success';
                    $response['message'] = 'No networks found';
                } else {
                    $response['data'] = $rows;
                    $response['status'] = 'success';
                    $response['message'] = 'Network statuses fetched successfully';
                }
            } catch (PDOException $e) {
                error_log("Database error in network-status: " . $e->getMessage());
                http_response_code(503);
                $response['status'] = 'error';
                $response['message'] = 'Database service is currently unavailable';
            } catch (Exception $e) {
                error_log("Error in network-status: " . $e->getMessage());
                http_response_code(500);
                $response['status'] = 'error';
                $response['message'] = $e->getMessage();
            }
            break;


        case 'data-plans':
            $service = new DataService();
            $networkIdInput = isset($_GET['network']) ? $_GET['network'] : null;
            $networkId = resolveNetworkIdFromInput($networkIdInput);
            $dataType = isset($_GET['type']) ? $_GET['type'] : null;
            // Allow client to pass user id so service can select pricing (userprice/vendorprice)
            $userId = isset($_GET['user_id']) ? $_GET['user_id'] : (isset($_GET['userId']) ? $_GET['userId'] : null);
            $response['data'] = $service->getDataPlans($networkId, $dataType, $userId);
            $response['status'] = 'success';
            $response['message'] = 'Data plans fetched successfully';
            break;

    case 'purchase-data':
            if ($requestMethod !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents("php://input"));

            // Debug the received data
            error_log("Received data purchase request with data: " . print_r($data, true));

            // Map the incoming parameters to our internal format
            $networkIdInput = $data->network ?? $data->network_id ?? $data->networkId ?? null;
            $networkId = resolveNetworkIdFromInput($networkIdInput);
            $phone = $data->mobile_number ?? $data->phone ?? $data->phoneNumber ?? null;
            $planId = $data->plan ?? $data->plan_id ?? $data->planId ?? null;
            $userId = $data->user_id ?? $data->userId ?? null;

            error_log("Mapped parameters:");
            error_log("Network ID: " . $networkId);
            error_log("Phone: " . $phone);
            error_log("Plan ID: " . $planId);
            error_log("User ID: " . $userId);

            if (!$networkId || !$phone || !$planId || !$userId) {
                error_log("Missing parameters. Required: network/network_id, mobile_number/phone, plan/plan_id, user_id");
                error_log("Received raw data: " . print_r($data, true));
                error_log("Mapped values: " . json_encode([
                    'network_id' => $networkId,
                    'phone' => $phone,
                    'plan_id' => $planId,
                    'user_id' => $userId
                ]));
                throw new Exception('Missing required parameters');
            }

            $service = new DataService();
            // Let the service accept numeric id; pass resolved network id
            $svcResult = $service->purchaseData($networkId, $phone, $planId, $userId);

            // Normalize and propagate to top-level response
            $response['data'] = $svcResult['data'] ?? $svcResult;
            $response['message'] = $svcResult['message'] ?? '';
            $response['status'] = $svcResult['status'] ?? 'failed';

            // Set HTTP response code based on service status
            if ($response['status'] === 'failed') {
                http_response_code(500);
            } else {
                // success or processing
                http_response_code(200);
            }
            break;

        case 'cable-plans':
            $service = new CableService();
            $providerId = isset($_GET['provider']) ? $_GET['provider'] : null;
            $response['data'] = $service->getCablePlans($providerId);
            $response['status'] = 'success';
            $response['message'] = 'Cable plans fetched successfully';
            break;

        case 'electricity-providers':
            $service = new ElectricityService();
            $response['data'] = $service->getElectricityProviders();
            $response['status'] = 'success';
            $response['message'] = 'Electricity providers fetched successfully';
            break;

        case 'validate-meter':
            if ($requestMethod !== 'POST') {
                throw new Exception("Invalid request method");
            }

            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->meterNumber) || !isset($data->providerId)) {
                throw new Exception("Missing required parameters");
            }

            $service = new ElectricityService();
            // The service returns an array with keys: status, message, data
            // Propagate that result directly to the top-level response to avoid double-wrapping
            $svcResult = $service->validateMeterNumber(
                $data->meterNumber,
                $data->providerId,
                $data->meterType ?? 'prepaid'
            );

            // If service returned an HTTP-like status, map to router response
            $response['status'] = $svcResult['status'] ?? 'error';
            $response['message'] = $svcResult['message'] ?? '';
            $response['data'] = $svcResult['data'] ?? null;

            // Set HTTP response code based on service status
            if ($response['status'] === 'error' || $response['status'] === 'failed') {
                http_response_code(500);
            } else {
                http_response_code(200);
            }

            break;

        case 'purchase-electricity':
            if ($requestMethod !== 'POST') {
                throw new Exception("Invalid request method");
            }

            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->meterNumber) || !isset($data->providerId) || !isset($data->amount)) {
                throw new Exception("Missing required parameters");
            }

            $service = new ElectricityService();
            $svcResult = $service->purchaseElectricity(
                $data->meterNumber,
                $data->providerId,
                $data->amount,
                $data->meterType ?? 'prepaid',
                $data->phone ?? ''
            );

            $response['status'] = $svcResult['status'] ?? 'error';
            $response['message'] = $svcResult['message'] ?? '';
            $response['data'] = $svcResult['data'] ?? null;

            if ($response['status'] === 'error' || $response['status'] === 'failed') {
                http_response_code(500);
            } else {
                http_response_code(200);
            }
            break;

        case 'validate-iuc':
            if ($requestMethod !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->iucNumber) || !isset($data->providerId)) {
                throw new Exception('Missing required parameters');
            }

            $service = new CableService();
            $response['data'] = $service->validateIUCNumber($data->iucNumber, $data->providerId);
            $response['status'] = 'success';
            $response['message'] = 'IUC number validated successfully';
            break;

        case 'cable-subscription':
            if ($requestMethod !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents("php://input"));
            if (
                !isset($data->providerId) || !isset($data->planId) ||
                !isset($data->iucNumber) || !isset($data->phoneNumber) ||
                !isset($data->amount) || !isset($data->pin)
            ) {
                throw new Exception('Missing required parameters');
            }

            $service = new CableService();
            $response['data'] = $service->processCableSubscription(
                $data->providerId,
                $data->planId,
                $data->iucNumber,
                $data->phoneNumber,
                $data->amount,
                $data->pin
            );
            $response['status'] = 'success';
            $response['message'] = 'Cable subscription processed successfully';
            break;

        case 'exam-providers':
            $service = new ExamPinService();
            $result = $service->getExamProviders();
            $response['data'] = $result['data'];
            $response['status'] = $result['status'];
            $response['message'] = $result['message'];
            break;

        case 'exam-purchase':
            if ($requestMethod !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents("php://input"));
            error_log("Exam purchase request data: " . print_r($data, true));

            if (!isset($data->examId) || !isset($data->quantity) || !isset($data->userId) || !isset($data->pin)) {
                throw new Exception('Missing required parameters: examId, quantity, userId, pin');
            }

            $service = new ExamPinService();
            $result = $service->purchaseExamPin($data->examId, $data->quantity, $data->userId);

            $response['status'] = $result['status'];
            $response['message'] = $result['message'];
            $response['data'] = $result['data'];
            break;

        case 'recharge-card-plans':
            $service = new RechargeCardService();
            $response['data'] = $service->getRechargeCardPlans();
            $response['status'] = 'success';
            $response['message'] = 'Recharge card plans fetched successfully';
            break;

        case 'recharge-pin-plans':
            $service = new RechargePinService();
            $networkInput = isset($_GET['network']) ? $_GET['network'] : null;
            $networkId = resolveNetworkIdFromInput($networkInput);
            $response['data'] = $service->getAvailablePins($networkId);
            $response['status'] = 'success';
            $response['message'] = 'Recharge pin plans fetched successfully';
            break;

        case 'data-pin-plans':
            $service = new DataPinService();
            $networkInput = isset($_GET['network']) ? $_GET['network'] : null;
            $networkId = resolveNetworkIdFromInput($networkInput);
            $type = isset($_GET['type']) ? $_GET['type'] : null;
            $userId = isset($_GET['user_id']) ? $_GET['user_id'] : (isset($_GET['userId']) ? $_GET['userId'] : null);

            try {
                $result = $service->getDataPinPlans($networkId, $type, $userId);
                $response['status'] = $result['status'];
                $response['message'] = $result['message'];
                $response['data'] = $result['data'];
            } catch (Exception $e) {
                $response['status'] = 'error';
                $response['message'] = $e->getMessage();
                $response['data'] = null;
            }
            break;

        case 'purchase-data-pin':
            if ($requestMethod !== 'POST') {
                http_response_code(405);
                $response['message'] = 'Method not allowed';
                break;
            }

            $data = json_decode(file_get_contents("php://input"));
            error_log("Data pin purchase request data: " . print_r($data, true));

            // Validate required parameters
            if (!isset($data->plan) || !isset($data->quantity) || !isset($data->name_on_card) || !isset($data->userId)) {
                error_log("Missing required parameters. Received: " . json_encode($data));
                http_response_code(400);
                $response['status'] = 'error';
                $response['message'] = 'Missing required parameters. Required: plan, quantity, name_on_card, userId';
                break;
            }

            try {
                $service = new DataPinService();
                $result = $service->purchaseDataPin(
                    $data->plan,
                    $data->quantity,
                    $data->name_on_card,
                    $data->userId
                );

                $response['status'] = $result['status'];
                $response['message'] = $result['message'];
                $response['data'] = $result['data'];
            } catch (Exception $e) {
                error_log("Error in data pin purchase: " . $e->getMessage());
                http_response_code(500);
                $response['status'] = 'error';
                $response['message'] = $e->getMessage();
            }
            break;

        case 'generate-account':
            // New behavior: backend decides which bank to create for the user.
            if ($requestMethod !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->user_id)) {
                throw new Exception('Missing required parameters: user_id');
            }

            require_once __DIR__ . '/../config/database.php';
            require_once __DIR__ . '/../models/user.php';

            try {
                error_log("Processing account generation request for user_id: " . $data->user_id);
                $db = new Database();

                // Get user details
                $query = "SELECT sFname, sLname, sPhone, sEmail FROM subscribers WHERE sId = ?";
                error_log("Executing query with user_id: " . $data->user_id);
                $result = $db->query($query, [$data->user_id]);

                if (empty($result)) {
                    throw new Exception('User not found');
                }

                $user = $result[0];
                error_log("User data found: " . print_r($user, true));

                // If user already has any account (sBankNo or sSterlingBank), return it
                $checkQuery = "SELECT sBankNo, sSterlingBank, sBankName FROM subscribers WHERE sId = ?";
                $checkResult = $db->query($checkQuery, [$data->user_id]);

                if (!empty($checkResult)) {
                    $existing = $checkResult[0];
                    if (!empty($existing['sBankNo'])) {
                        $response['status'] = 'success';
                        $response['message'] = 'Account already exists';
                        $response['data'] = [
                            'account_number' => $existing['sBankNo'],
                            'bank_name' => $existing['sBankName'] ?? 'StroWallet'
                        ];
                        break;
                    }
                    if (!empty($existing['sSterlingBank'])) {
                        $response['status'] = 'success';
                        $response['message'] = 'Account already exists';
                        $response['data'] = [
                            'account_number' => $existing['sSterlingBank'],
                            'bank_name' => $existing['sBankName'] ?? 'Sterling Bank'
                        ];
                        break;
                    }
                }

                // Create virtual account (StroWallet / backend decides which bank)
                $userModel = new User($db);
                error_log("Attempting to create virtual account for user ID: " . $data->user_id);

                $accountCreated = $userModel->createVirtualAccount(
                    $data->user_id,
                    $user['sFname'],
                    $user['sLname'],
                    $user['sPhone'],
                    $user['sEmail']
                );

                if ($accountCreated) {
                    // Fetch the newly created account number and bank name
                    $fetchQuery = "SELECT sBankNo, sSterlingBank, sBankName FROM subscribers WHERE sId = ?";
                    $fetchResult = $db->query($fetchQuery, [$data->user_id]);

                    if (!empty($fetchResult)) {
                        $accountInfo = $fetchResult[0];
                        // Prefer sBankNo (StroWallet/Noma) if present, otherwise sSterlingBank
                        $acct = !empty($accountInfo['sBankNo']) ? $accountInfo['sBankNo'] : $accountInfo['sSterlingBank'];
                        $response['status'] = 'success';
                        $response['message'] = 'Account generated successfully';
                        $response['data'] = [
                            'account_number' => $acct,
                            'bank_name' => $accountInfo['sBankName'] ?? ''
                        ];
                    } else {
                        throw new Exception('Account created but unable to fetch details');
                    }
                } else {
                    throw new Exception('Failed to generate account. Please try again later.');
                }
            } catch (Exception $e) {
                error_log("Error in generate-account: " . $e->getMessage());
                throw $e;
            }
            break;

        case 'generate-palmpay-paga':
            // Generate Palmpay and Paga accounts for a user. This is a placeholder
            // implementation: if the subscriber already has sPaga or sPalmpayBank
            // populated we return them. Integration with the payment gateway to
            // create virtual accounts will be implemented when gateway docs are provided.
            if ($requestMethod !== 'POST') {
                http_response_code(405);
                $response['message'] = 'Method not allowed';
                break;
            }

            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->user_id)) {
                http_response_code(400);
                $response['message'] = 'Missing required parameter: user_id';
                break;
            }

            try {
                $db = new Database();

                // Ensure user exists
                $query = "SELECT sFname, sLname, sPhone, sEmail, sPaga,sPaga, sPalmpayBank FROM subscribers WHERE sId = ?";
                $rows = $db->query($query, [$data->user_id]);
                if (empty($rows)) {
                    throw new Exception('User not found');
                }

                $user = $rows[0];
                $paga = $user['sPaga'] ?? '';
                $palmpay = $user['sPalmpayBank'] ?? '';

                // If we already have values, return them immediately
                if (!empty($paga) || !empty($palmpay)) {
                    $response['status'] = 'success';
                    $response['message'] = 'Accounts fetched';
                    $response['data'] = [
                        'paga_account' => $paga,
                        'palmpay_account' => $palmpay,
                    ];
                    http_response_code(200);
                    break;
                }

                // Fetch Aspfiy API key and webhook from apiconfigs (names: asfiyApi, asfiyWebhook)
                $cfg = $db->query("SELECT name, value FROM apiconfigs WHERE name IN (?, ?)", ['asfiyApi', 'asfiyWebhook']);
                $aspKey = '';
                $webhookUrl = '';
                if (!empty($cfg)) {
                    foreach ($cfg as $c) {
                        if ($c['name'] === 'asfiyApi') $aspKey = $c['value'];
                        if ($c['name'] === 'asfiyWebhook') $webhookUrl = $c['value'];
                    }
                }
                if (empty($aspKey)) {
                    throw new Exception('Aspfiy API key not configured (apiconfigs.name=\'asfiyApi\')');
                }

                // Helper: perform POST to Aspfiy reserve endpoints
                $callAspfiy = function($endpoint, $payload) use ($aspKey) {
                    $url = rtrim('https://api-v1.aspfiy.com', '/') . '/' . ltrim($endpoint, '/');
                    $ch = curl_init($url);
                    $body = json_encode($payload);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $aspKey,
                        'Content-Length: ' . strlen($body)
                    ]);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                    $resp = curl_exec($ch);
                    $err = curl_error($ch);
                    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($err) {
                        throw new Exception('HTTP request error: ' . $err);
                    }

                    $decoded = json_decode($resp, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('Invalid JSON from Aspfiy: ' . json_last_error_msg());
                    }

                    return ['code' => $code, 'body' => $decoded];
                };

                // Build reference and webhook (use application config or fallback)
                $referenceBase = 'PALMPAGA-' . $data->user_id . '-' . time();
                // If webhook was found in apiconfigs use it, otherwise fall back to global
                $webhookUrl = !empty($webhookUrl) ? $webhookUrl : (isset($GLOBALS['ASPFIY_WEBHOOK']) && !empty($GLOBALS['ASPFIY_WEBHOOK']) ? $GLOBALS['ASPFIY_WEBHOOK'] : null);

                // Prepare common payload fields
                $firstName = $user['sFname'] ?? '';
                $lastName = $user['sLname'] ?? '';
                $phone = $user['sPhone'] ?? '';

                // Reserve Paga
                $pagaPayload = [
                    'reference' => $referenceBase . '-PAGA',
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'phone' => $phone,
                    // include email if available (Aspfiy may require it)
                    'email' => $user['sEmail'] ?? '',
                    // webhookUrl is required by Aspfiy - include if configured
                ];
                if (!empty($webhookUrl)) $pagaPayload['webhookUrl'] = $webhookUrl;

                error_log('Calling Aspfiy reserve-paga with payload: ' . json_encode($pagaPayload));
                $pagaResp = $callAspfiy('reserve-paga/', $pagaPayload);

                // Reserve Palmpay
                $palmpayPayload = [
                    'reference' => $referenceBase . '-PALMPAY',
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'phone' => $phone,
                    // include email if available
                    'email' => $user['sEmail'] ?? '',
                ];
                if (!empty($webhookUrl)) $palmpayPayload['webhookUrl'] = $webhookUrl;

                error_log('Calling Aspfiy reserve-palmpay with payload: ' . json_encode($palmpayPayload));
                $palmpayResp = $callAspfiy('reserve-palmpay/', $palmpayPayload);

                // Interpret responses and persist to subscribers
                $pagaAcct = '';
                $palmpayAcct = '';
                $pagaAcctName = '';
                $pagaBankName = '';
                $palmpayAcctName = '';
                $palmpayBankName = '';

                // Robust success detection: some 200 responses may not include `data` and instead
                // return status/message. Consider success when `data` exists or `status` indicates success.
                if ($pagaResp['code'] >= 200 && $pagaResp['code'] < 300) {
                    $body = $pagaResp['body'];
                    $pagaOk = (!empty($body['data'])) || (!empty($body['status']) && (strtolower((string)$body['status']) === 'success' || $body['status'] === true || $body['status'] === 'true'));
                    if ($pagaOk && !empty($body['data']) && is_array($body['data'])) {
                        $d = $body['data'];
                        // Aspfiy may return account_number/account_name/bank_name
                        $pagaAcct = $d['account_number'] ?? $d['account'] ?? $d['accountNo'] ?? $d['reference'] ?? '';
                        $pagaAcctName = $d['account_name'] ?? $d['name'] ?? '';
                        $pagaBankName = $d['bank_name'] ?? $d['bank'] ?? '';
                        // If the extracted value is itself an array/object, try to normalize
                        if (is_array($pagaAcct) || is_object($pagaAcct)) {
                            $cand = (array)$pagaAcct;
                            $pagaAcct = $cand['account_number'] ?? $cand['accountNo'] ?? $cand['account'] ?? $cand['reference'] ?? '';
                            // Also try to pull name/bank from nested structure if empty
                            if (empty($pagaAcctName)) $pagaAcctName = $cand['account_name'] ?? $cand['name'] ?? '';
                            if (empty($pagaBankName)) $pagaBankName = $cand['bank_name'] ?? $cand['bank'] ?? '';
                        }
                        if (!empty($pagaAcct)) {
                            error_log('Aspfiy reserve-paga created account: ' . $pagaAcct);
                        } else {
                            error_log('Aspfiy reserve-paga response (no account number): ' . print_r($pagaResp, true));
                        }
                    } else {
                        // Not a success or no account returned
                        error_log('Aspfiy reserve-paga response (no account): ' . print_r($pagaResp, true));
                    }
                } else {
                    error_log('Aspfiy reserve-paga failed (http): ' . print_r($pagaResp, true));
                }

                if ($palmpayResp['code'] >= 200 && $palmpayResp['code'] < 300) {
                    $body = $palmpayResp['body'];
                    $palmOk = (!empty($body['data'])) || (!empty($body['status']) && (strtolower((string)$body['status']) === 'success' || $body['status'] === true || $body['status'] === 'true'));
                    if ($palmOk && !empty($body['data']) && is_array($body['data'])) {
                        $d = $body['data'];
                        $palmpayAcct = $d['account_number'] ?? $d['account'] ?? $d['accountNo'] ?? $d['reference'] ?? '';
                        $palmpayAcctName = $d['account_name'] ?? $d['name'] ?? '';
                        $palmpayBankName = $d['bank_name'] ?? $d['bank'] ?? '';
                        if (is_array($palmpayAcct) || is_object($palmpayAcct)) {
                            $cand = (array)$palmpayAcct;
                            $palmpayAcct = $cand['account_number'] ?? $cand['accountNo'] ?? $cand['account'] ?? $cand['reference'] ?? '';
                            if (empty($palmpayAcctName)) $palmpayAcctName = $cand['account_name'] ?? $cand['name'] ?? '';
                            if (empty($palmpayBankName)) $palmpayBankName = $cand['bank_name'] ?? $cand['bank'] ?? '';
                        }
                        if (!empty($palmpayAcct)) {
                            error_log('Aspfiy reserve-palmpay created account: ' . $palmpayAcct);
                        } else {
                            error_log('Aspfiy reserve-palmpay response (no account number): ' . print_r($palmpayResp, true));
                        }
                    } else {
                        error_log('Aspfiy reserve-palmpay response (no account): ' . print_r($palmpayResp, true));
                    }
                } else {
                    error_log('Aspfiy reserve-palmpay failed (http): ' . print_r($palmpayResp, true));
                }

                // Update subscribers table when we have values
                // Normalize account values as plain strings
                $pagaAcct = is_null($pagaAcct) ? '' : trim((string)$pagaAcct);
                $palmpayAcct = is_null($palmpayAcct) ? '' : trim((string)$palmpayAcct);

                if (!empty($pagaAcct) || !empty($palmpayAcct)) {
                    $updateParts = [];
                    $params = [];
                    if (!empty($pagaAcct)) {
                        $updateParts[] = 'sPaga = ?';
                        $params[] = $pagaAcct;
                    }
                    if (!empty($palmpayAcct)) {
                        $updateParts[] = 'sPalmpayBank = ?';
                        $params[] = $palmpayAcct;
                    }
                    // Always mark that accounts were generated in the app
                    $updateParts[] = 'sBankName = ?';
                    $params[] = 'app';
                    $params[] = $data->user_id;
                    $updateQuery = 'UPDATE subscribers SET ' . implode(', ', $updateParts) . ' WHERE sId = ?';
                    $db->query($updateQuery, $params);

                    $response['status'] = 'success';
                    $response['message'] = 'Accounts generated/updated';
                    $response['data'] = [
                        'paga_account' => $pagaAcct,
                        'paga_account_name' => $pagaAcctName,
                        'paga_bank_name' => $pagaBankName,
                        'palmpay_account' => $palmpayAcct,
                        'palmpay_account_name' => $palmpayAcctName,
                        'palmpay_bank_name' => $palmpayBankName,
                    ];
                    http_response_code(200);
                } else {
                    $response['status'] = 'error';
                    $response['message'] = 'Failed to reserve Palmpay/Paga accounts. See server logs.';
                    http_response_code(502);
                }
            } catch (PDOException $e) {
                error_log('Database error in generate-palmpay-paga: ' . $e->getMessage());
                http_response_code(503);
                $response['status'] = 'error';
                $response['message'] = 'Database service is currently unavailable.';
            } catch (Exception $e) {
                error_log('Error in generate-palmpay-paga: ' . $e->getMessage());
                http_response_code(500);
                $response['status'] = 'error';
                $response['message'] = $e->getMessage();
            }
            break;

        case 'update-pin':
            if ($requestMethod !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents("php://input"));
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON: ' . json_last_error_msg());
            }

            if (!isset($data->user_id) || !isset($data->pin)) {
                throw new Exception('Missing required parameters: user_id, pin');
            }

            try {
                $userService = new UserService();
                $updated = $userService->updatePin($data->user_id, $data->pin);
                if ($updated) {
                    $response['status'] = 'success';
                    $response['message'] = 'PIN updated successfully';
                    $response['data'] = null;
                    http_response_code(200);
                } else {
                    throw new Exception('Failed to update PIN');
                }
            } catch (PDOException $e) {
                error_log("Database error in update-pin: " . $e->getMessage());
                http_response_code(503);
                throw new Exception("Database service is currently unavailable. Please try again later.");
            } catch (Exception $e) {
                error_log("Error in update-pin: " . $e->getMessage());
                throw $e;
            }
            break;

        case 'transactions':
            $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
            if (!$userId) {
                throw new Exception('User ID is required');
            }

        // Select fields that actually exist in the `transactions` table and include oldbal/newbal
        $query = "SELECT tId, sId, transref, servicename, servicedesc, amount, status, 
                  COALESCE(oldbal, NULL) as oldbal, COALESCE(newbal, NULL) as newbal,
                  profit, date, api_response, api_response_log
              FROM transactions
              WHERE sId = ?
              ORDER BY date DESC";
            $db = new Database();
            $transactions = $db->query($query, [$userId]);

            // Map oldbal/newbal to string values to send to client (keep null if not present)
            foreach ($transactions as &$tx) {
                if (isset($tx['oldbal']) && $tx['oldbal'] !== null) {
                    $tx['oldbal'] = (string)$tx['oldbal'];
                } else {
                    $tx['oldbal'] = null;
                }
                if (isset($tx['newbal']) && $tx['newbal'] !== null) {
                    $tx['newbal'] = (string)$tx['newbal'];
                } else {
                    $tx['newbal'] = null;
                }
                // Keep response keys consistent and present for the client
                if (!isset($tx['transref'])) $tx['transref'] = '';
                if (!isset($tx['servicename'])) $tx['servicename'] = '';
                if (!isset($tx['servicedesc'])) $tx['servicedesc'] = '';
            }

            $response['data'] = $transactions;
            $response['status'] = 'success';
            $response['message'] = 'Transactions fetched successfully';
            break;

        case 'beneficiaries':
            // GET /api/beneficiaries?user_id=123
            $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
            if (!$userId) {
                throw new Exception('Missing user_id parameter');
            }
            try {
                $svc = new BeneficiaryService();
                $rows = $svc->listByUser($userId);
                $response['data'] = $rows;
                $response['status'] = 'success';
                $response['message'] = 'Beneficiaries fetched successfully';
            } catch (PDOException $e) {
                http_response_code(503);
                throw new Exception('Database service is unavailable');
            }
            break;

        case 'beneficiary':
            // POST to create, PUT to update, DELETE to remove
            if ($requestMethod === 'POST') {
                $data = json_decode(file_get_contents('php://input'));
                if (!isset($data->user_id) || !isset($data->name) || !isset($data->phone)) {
                    throw new Exception('Missing required parameters');
                }
                $svc = new BeneficiaryService();
                $insertId = $svc->create($data->user_id, $data->name, $data->phone);
                $response['status'] = 'success';
                $response['message'] = 'Beneficiary added';
                $response['data'] = ['id' => $insertId];
            } else if ($requestMethod === 'PUT') {
                $data = json_decode(file_get_contents('php://input'));
                if (!isset($data->id) || !isset($data->user_id) || !isset($data->name) || !isset($data->phone)) {
                    throw new Exception('Missing required parameters');
                }
                $svc = new BeneficiaryService();
                $ok = $svc->update($data->id, $data->user_id, $data->name, $data->phone);
                $response['status'] = $ok ? 'success' : 'failed';
                $response['message'] = $ok ? 'Beneficiary updated' : 'Update failed';
            } else if ($requestMethod === 'DELETE') {
                // Expect JSON body { id: ..., user_id: ... }
                $data = json_decode(file_get_contents('php://input'));
                if (!isset($data->id) || !isset($data->user_id)) {
                    throw new Exception('Missing required parameters');
                }
                $svc = new BeneficiaryService();
                $ok = $svc->delete($data->id, $data->user_id);
                $response['status'] = $ok ? 'success' : 'failed';
                $response['message'] = $ok ? 'Beneficiary deleted' : 'Delete failed';
            } else {
                throw new Exception('Method not allowed');
            }
            break;

        case 'manual-payments':
            // Fetch manual payment records. Optional query param: user_id
            if ($requestMethod !== 'GET') {
                http_response_code(405);
                throw new Exception('Method not allowed');
            }

            // Optional filter by user id (accept user_id or userId)
            $userId = isset($_GET['user_id']) ? $_GET['user_id'] : (isset($_GET['userId']) ? $_GET['userId'] : null);

            require_once __DIR__ . '/../config/database.php';

            try {
                $db = new Database();

                if ($userId) {
                    $query = "SELECT * FROM manualfunds WHERE sId = ? ORDER BY dPosted DESC";
                    $payments = $db->query($query, [$userId]);
                } else {
                    $query = "SELECT * FROM manualfunds ORDER BY dPosted DESC";
                    $payments = $db->query($query);
                }

                $response['data'] = $payments;
                $response['status'] = 'success';
                $response['message'] = 'Manual payments fetched successfully';
            } catch (PDOException $e) {
                error_log("Database error in manual-payments: " . $e->getMessage());
                http_response_code(503);
                $response['status'] = 'error';
                $response['message'] = 'Database service is currently unavailable. Please try again later.';
            } catch (Exception $e) {
                error_log("Error in manual-payments: " . $e->getMessage());
                http_response_code(500);
                $response['status'] = 'error';
                $response['message'] = $e->getMessage();
            }
            break;

        case 'manual-payment':
            // Fetch a single manual payment destination (preferred: status=1), optional ?bank filter
            if ($requestMethod !== 'GET') {
                http_response_code(405);
                throw new Exception('Method not allowed');
            }
            // Return account details stored in sitesettings table (single row)
            require_once __DIR__ . '/../config/database.php';

            try {
                $db = new Database();
                // sitesettings usually has one row (sId=1). Fetch latest just in case.
                $query = "SELECT accountname, accountno, bankname FROM sitesettings ORDER BY sId DESC LIMIT 1";
                $rows = $db->query($query);

                if (!empty($rows)) {
                    $settings = $rows[0];
                    $response['data'] = [
                        'account_name' => $settings['accountname'] ?? '',
                        'account_number' => $settings['accountno'] ?? '',
                        'bank_name' => $settings['bankname'] ?? '',
                    ];
                    $response['status'] = 'success';
                    $response['message'] = 'Manual payment settings fetched successfully';
                } else {
                    $response['data'] = null;
                    $response['status'] = 'error';
                    $response['message'] = 'Site settings not found';
                }
            } catch (PDOException $e) {
                error_log("Database error in manual-payment (sitesettings): " . $e->getMessage());
                http_response_code(503);
                $response['status'] = 'error';
                $response['message'] = 'Database service is currently unavailable. Please try again later.';
            } catch (Exception $e) {
                error_log("Error in manual-payment (sitesettings): " . $e->getMessage());
                http_response_code(500);
                $response['status'] = 'error';
                $response['message'] = $e->getMessage();
            }
            break;

        case 'send-manual-proof':
            // Accepts POST with JSON body: amount, bank, sender, optional account_number, account_name, bank_name, user_id
            if ($requestMethod !== 'POST') {
                http_response_code(405);
                $response['message'] = 'Method not allowed';
                break;
            }

            $rawInput = file_get_contents("php://input");
            error_log("send-manual-proof raw input: " . $rawInput);

            $data = json_decode($rawInput);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                $response['message'] = 'Invalid JSON';
                break;
            }

            if (!isset($data->amount) || !isset($data->bank) || !isset($data->sender)) {
                http_response_code(400);
                $response['message'] = 'Missing required parameters: amount, bank, sender';
                break;
            }

            $amount = $data->amount;
            $bank = $data->bank;
            $sender = $data->sender;
            $accountNumber = isset($data->account_number) ? $data->account_number : '';
            $accountName = isset($data->account_name) ? $data->account_name : '';
            $bankName = isset($data->bank_name) ? $data->bank_name : '';

            // Load PHPMailer
            require_once __DIR__ . '/../vendor/autoload.php';
            

            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

                // SMTP settings (reusing existing project settings)
                $mail->isSMTP();
                $mail->Host = 'mail.mkdata.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'no-reply@mkdata.com';
                $mail->Password = ']xG28YL,APm-+xbx';

                // Use STARTTLS for port 587
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('no-reply@mkdata.com', 'Binali One Data');
                $mail->addAddress('Muhammadbinali1234@gmail.com');

                $mail->isHTML(true);
                $mail->Subject = 'Manual Payment Proof Submission';

                $body = "<h3>Manual Payment Proof</h3>";
                $body .= "<p><strong>Amount:</strong> ₦" . htmlspecialchars($amount) . "</p>";
                $body .= "<p><strong>Bank Provided:</strong> " . htmlspecialchars($bank) . "</p>";
                if (!empty($bankName)) $body .= "<p><strong>Bank Name:</strong> " . htmlspecialchars($bankName) . "</p>";
                if (!empty($accountNumber)) $body .= "<p><strong>Account Number:</strong> " . htmlspecialchars($accountNumber) . "</p>";
                if (!empty($accountName)) $body .= "<p><strong>Account Name:</strong> " . htmlspecialchars($accountName) . "</p>";
                $body .= "<p><strong>Sender:</strong> " . htmlspecialchars($sender) . "</p>";
                // prefer subscriber id sent by client (sId) but accept user_id for backwards compatibility
                $submittedSid = isset($data->sId) ? $data->sId : (isset($data->user_id) ? $data->user_id : '');
                if (!empty($submittedSid)) $body .= "<p><strong>Subscriber ID:</strong> " . htmlspecialchars($submittedSid) . "</p>";
                $body .= "<p>Posted at: " . date('Y-m-d H:i:s') . "</p>";

                $mail->Body = $body;
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<p>', '</p>'], "\n", $body));

                $mail->send();

                // Also insert the proof record into the manualfunds table
                try {
                    require_once __DIR__ . '/../config/database.php';
                    $db = new Database();
                    $conn = $db->getConnection();

                    // Build account field using what the user submitted. If the user sent
                    // account_name and account_number, prefer those. Fallback to sender
                    // name only when the user did not provide account info.
                    $accountField = trim((!empty($accountName) ? $accountName . ' ' : '') . (!empty($accountNumber) ? $accountNumber : ''));
                    if (empty($accountField)) {
                        $accountField = $sender;
                    }

                    // Prefer the explicit bank name provided by the user (bank_name),
                    // otherwise use the provided 'bank' value.
                    $methodField = !empty($bankName) ? $bankName : $bank;

                    // Prefer 'sId' (subscriber id) if provided by the client, else accept user_id.
                    $sId = isset($data->sId) ? $data->sId : (isset($data->user_id) ? $data->user_id : '');
                    $statusVal = 0; // default pending
                    $postedAt = date('Y-m-d H:i:s');

                    $insertStmt = $conn->prepare("INSERT INTO manualfunds (sId, amount, account, method, status, dPosted) VALUES (?, ?, ?, ?, ?, ?)");
                    $insertStmt->execute([$sId, $amount, $accountField, $methodField, $statusVal, $postedAt]);
                    $insertId = $conn->lastInsertId();

                    $response['status'] = 'success';
                    $response['message'] = 'Proof email sent and record saved successfully';
                    $response['data'] = ['insert_id' => $insertId];
                } catch (Exception $e) {
                    error_log('Error inserting manual proof into DB: ' . $e->getMessage());
                    http_response_code(500);
                    $response['status'] = 'error';
                    $response['message'] = 'Email sent but failed to save record: ' . $e->getMessage();
                    $response['data'] = null;
                }
            } catch (Exception $e) {
                error_log('Error sending manual proof email: ' . $e->getMessage());
                http_response_code(500);
                $response['status'] = 'error';
                $response['message'] = 'Unable to send email: ' . $e->getMessage();
            }

            break;

        case 'account-details':
            $subscriberId = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$subscriberId) {
                throw new Exception('Subscriber ID is required');
            }

            $query = "SELECT sId, sFname, sLname, sEmail, sPhone, sType, sWallet, sRefWallet,
                sBankNo, sSterlingBank, sBankName, sRolexBank, sFidelityBank, sAsfiyBank,
                s9PSBBank, sPayvesselBank, sPaga, sPagaBank, sPalmpayBank, sRegStatus, sLastActivity,
                sAccountLimit
                FROM subscribers WHERE sId = ?";

            $db = new Database();
            $result = $db->query($query, [$subscriberId]);

            if (empty($result)) {
                throw new Exception('Account details not found');
            }

            $account = $result[0];
            error_log("Raw account data from database: " . print_r($account, true));

            $response['data'] = [
                'sId' => $account['sId'],
                'sFname' => $account['sFname'],
                'sLname' => $account['sLname'],
                'sEmail' => $account['sEmail'],
                'sPhone' => $account['sPhone'],
                'sType' => (int)$account['sType'],
                'sWallet' => (float)$account['sWallet'],
                'sRefWallet' => (float)$account['sRefWallet'],
                'sBankNo' => $account['sBankNo'],
                'sSterlingBank' => $account['sSterlingBank'],
                'sBankName' => $account['sBankName'],
                'sRolexBank' => $account['sRolexBank'],
                'sFidelityBank' => $account['sFidelityBank'],
                'sAsfiyBank' => $account['sAsfiyBank'],
                's9PSBBank' => $account['s9PSBBank'],
                'sPayvesselBank' => $account['sPayvesselBank'],
                'sPagaBank' => $account['sPagaBank'],
                'sPaga' => $account['sPaga'],
                'sPalmpayBank' => $account['sPalmpayBank'],
                'sAccountLimit' => $account['sAccountLimit']
            ];
            error_log("Formatted response data: " . json_encode($response['data'], JSON_PRETTY_PRINT));
            $response['status'] = 'success';
            $response['message'] = 'Account details fetched successfully';
            break;

        case 'subscriber':
            $subscriberId = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$subscriberId) {
                throw new Exception('Subscriber ID is required');
            }

            $query = "SELECT sId, sFname, sLname, sEmail, sPhone, sType, sWallet, sRefWallet, 
                sBankNo, sSterlingBank, sBankName, sRegStatus, sLastActivity,
                sRolexBank, sFidelityBank, sPaga ,sAsfiyBank, s9PSBBank, sPayvesselBank, 
                sPagaBank, sPalmpayBank 
                FROM subscribers WHERE sId = ?";

            $db = new Database();
            $result = $db->query($query, [$subscriberId]);

            if (empty($result)) {
                throw new Exception('Subscriber not found');
            }

            $subscriber = $result[0];
            // Format the response
            $response['data'] = [
                'sId' => $subscriber['sId'],
                'sFname' => $subscriber['sFname'],
                'sLname' => $subscriber['sLname'],
                'sEmail' => $subscriber['sEmail'],
                'sPhone' => $subscriber['sPhone'],
                'sType' => (int)$subscriber['sType'],
                'sWallet' => (float)$subscriber['sWallet'],
                'sRefWallet' => (float)$subscriber['sRefWallet'],
                'sBankNo' => $subscriber['sBankNo'],
                'sSterlingBank' => $subscriber['sSterlingBank'],
                'sBankName' => $subscriber['sBankName'],
                'sRolexBank' => $subscriber['sRolexBank'],
                'sFidelityBank' => $subscriber['sFidelityBank'],
                'sAsfiyBank' => $subscriber['sAsfiyBank'],
                's9PSBBank' => $subscriber['s9PSBBank'],
                'sPayvesselBank' => $subscriber['sPayvesselBank'],
                'sPagaBank' => $subscriber['sPagaBank'],
                'sPaga' => $subscriber['sPaga'],
                'sPalmpayBank' => $subscriber['sPalmpayBank'],
                'sRegStatus' => (int)$subscriber['sRegStatus'],
                'lastActivity' => $subscriber['sLastActivity']
            ];
            $response['status'] = 'success';
            $response['message'] = 'Subscriber details fetched successfully';
            break;

        case 'update-profile':
            if ($requestMethod !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents("php://input"));
            
            // Validate required fields
            if (!isset($data->user_id)) {
                throw new Exception('Missing required parameter: user_id');
            }

            if (!isset($data->fname) && !isset($data->lname) && !isset($data->new_password)) {
                throw new Exception('At least one field (fname, lname, or new_password) must be provided');
            }

            try {
                $user_service = new UserService();
                
                // Prepare update data
                $updates = [];
                $params = [];

                // Update first name if provided
                if (isset($data->fname) && !empty($data->fname)) {
                    $updates[] = 'sFname = ?';
                    $params[] = trim($data->fname);
                }

                // Update last name if provided
                if (isset($data->lname) && !empty($data->lname)) {
                    $updates[] = 'sLname = ?';
                    $params[] = trim($data->lname);
                }

                // Update password if provided
                if (isset($data->new_password) && !empty($data->new_password)) {
                    // Verify current password first
                    if (!isset($data->current_password) || empty($data->current_password)) {
                        throw new Exception('Current password is required to change password');
                    }

                    // Get current password hash from database
                    $db = new Database();
                    $result = $db->query('SELECT sPassword FROM subscribers WHERE sId = ? LIMIT 1', [$data->user_id]);
                    
                    if (empty($result)) {
                        throw new Exception('User not found');
                    }

                    // Verify current password
                    $currentHashedPassword = $result[0]['sPassword'];
                    if (!password_verify($data->current_password, $currentHashedPassword)) {
                        throw new Exception('Current password is incorrect');
                    }

                    // Hash new password
                    $hashedPassword = password_hash($data->new_password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $updates[] = 'sPassword = ?';
                    $params[] = $hashedPassword;
                }

                if (empty($updates)) {
                    throw new Exception('No valid updates provided');
                }

                // Add user_id to params for WHERE clause
                $params[] = $data->user_id;

                // Build and execute update query
                $updateQuery = 'UPDATE subscribers SET ' . implode(', ', $updates) . ' WHERE sId = ?';
                $db = new Database();
                $stmt = $db->getConnection()->prepare($updateQuery);
                $stmt->execute($params);

                if ($stmt->rowCount() > 0) {
                    // Fetch updated user data
                    $result = $db->query('SELECT sId, sFname, sLname, sEmail, sPhone FROM subscribers WHERE sId = ? LIMIT 1', [$data->user_id]);
                    
                    if (!empty($result)) {
                        $response['status'] = 'success';
                        $response['message'] = 'Profile updated successfully';
                        $response['data'] = [
                            'user_id' => $result[0]['sId'],
                            'fname' => $result[0]['sFname'],
                            'lname' => $result[0]['sLname'],
                            'email' => $result[0]['sEmail'],
                            'phone' => $result[0]['sPhone']
                        ];
                    } else {
                        throw new Exception('Failed to fetch updated profile');
                    }
                } else {
                    throw new Exception('Failed to update profile');
                }

            } catch (PDOException $e) {
                error_log("Database error in update-profile: " . $e->getMessage());
                http_response_code(503);
                throw new Exception("Database service is currently unavailable. Please try again later.");
            } catch (Exception $e) {
                error_log("Error in update-profile: " . $e->getMessage());
                throw $e;
            }
            break;

        default:
            error_log("No matching endpoint found for: " . $endpoint);
            http_response_code(404);
            $response['message'] = "Endpoint '/$endpoint' not found";
            break;
    }
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);

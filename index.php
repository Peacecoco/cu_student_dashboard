<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once __DIR__ . '/include/config.php';
require_once __DIR__ . '/include/classes.php';
$idcard = new IDCard($con);
$method = $_SERVER['REQUEST_METHOD'];
$action = strtolower(trim($_GET['action'] ?? ''));

if ($method === 'GET' && isset($_GET['identifier'])) {
    $idcard->getApplicationsByIdentifier($_GET['identifier']);
}
if ($method === 'GET' && $action === 'settings') {
    $idcard->getSettings($_GET['applicationtype'] ?? null);
}
if ($method === 'GET' && $action === 'paymentinvoice') {
    $idcard->getPaymentInvoice($_GET['identifier'] ?? null, $_GET['ref'] ?? null);
}
if ($method === 'GET' && $action === 'paymenthistory') {
    $idcard->getPaymentHistory($_GET['identifier'] ?? null, $_GET['ref'] ?? null);
}
if ($method === 'POST' && $action === 'submit') {
    $idcard->submitApplication($_POST, $_FILES);
}
if ($method === 'POST' && $action === 'processpayment') {
    $body = json_decode(file_get_contents('php://input'), true);
    $idcard->processPayment(is_array($body) ? $body : []);
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request.']);

<?php
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json');header('Cache-Control: no-store');
try { require_once __DIR__.'/include/session.php'; $student=currentStudent(); }
catch (DomainException $e) { http_response_code(401);echo json_encode(['success'=>false,'message'=>'Please sign in through the CU student portal to continue.']);exit; }
catch (Throwable $e) { http_response_code(503);echo json_encode(['success'=>false,'message'=>'Student sign-in is temporarily unavailable.']);exit; }
$method=$_SERVER['REQUEST_METHOD'];
if ($method==='POST') requireStudentCsrf($student);
$csrf=studentCsrfToken($student);session_write_close();
require_once __DIR__.'/include/config.php';require_once __DIR__.'/include/classes.php';
try {
    $outcome=getenv('CU_STUDENT_SIMULATOR_RESULT') ?: 'paid';
    if (!in_array($outcome,['paid','failed'],true)) throw new RuntimeException('Invalid simulator configuration.');
    $lifecycle=new CU\IdCard\Lifecycle($con,new CU\IdCard\LocalPaymentSimulator($outcome==='paid'));
    $module=new IDCard($con,$lifecycle,$student);
    $action=strtolower(trim((string)($_GET['action'] ?? '')));
    if ($action==='' && isset($_GET['identifier'])) $action='applications';
    if (isset($_GET['identifier'])) $module->validateMatric((string)$_GET['identifier']);
    $body=[];
    if ($method==='POST') {
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $body=json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);
            if (!is_array($body)) throw new InvalidArgumentException('Invalid request body.');
        } else $body=$_POST;
        if (isset($body['matricnumber'])) $module->validateMatric((string)$body['matricnumber']);
    }
    $ref=(string)($_GET['ref'] ?? $body['paymentreference'] ?? $body['referencenumber'] ?? '');
    $data=match ($method.':'.$action) {
        'GET:session'=>['csrf'=>$csrf,'student'=>$module->student()],
        // The server identity is authoritative; the optional matric is retained only for legacy callers.
        'GET:eligibility'=>$module->eligibility((string)($_GET['matricnumber'] ?? $student->matric),$_GET['applicationtype'] ?? null),
        'GET:settings'=>$module->settings(),
        'GET:applications'=>$module->applications(),
        'GET:checkout','GET:paymentinvoice'=>$module->checkout($ref),
        'GET:paymenthistory'=>$module->paymentDetails($ref),
        'GET:history'=>$module->history($ref),
        'POST:submit','POST:begincheckout'=>$module->beginCheckout($body,$_FILES),
        'POST:processpayment'=>$lifecycle->completePayment($student,$ref),
        'POST:cancelcheckout'=>$module->cancelCheckout($ref),
        'POST:requestrefund'=>$module->requestRefund($ref),
        default=>throw new InvalidArgumentException('Invalid request.'),
    };
    echo json_encode(['success'=>true,'message'=>'Request completed.','data'=>$data],JSON_THROW_ON_ERROR);
} catch (DomainException $e) {
    $message=$e->getMessage();
    if (in_array($message,['Application not found.','Checkout not found.'],true)) $message='We could not find an eligible application with that reference ID.';
    http_response_code(409);echo json_encode(['success'=>false,'message'=>$message]);
} catch (InvalidArgumentException|JsonException $e) {
    http_response_code(400);echo json_encode(['success'=>false,'message'=>'Please check the supplied request details.']);
} catch (Throwable $e) {
    error_log('Student ID-card request failed: '.$e->getMessage());
    http_response_code(500);echo json_encode(['success'=>false,'message'=>'Unable to complete this request. Please refresh and try again.']);
}

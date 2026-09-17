<?php
declare(strict_types=1);
require_once __DIR__.'/../../idcard-system/shared/lifecycle/bootstrap.php';
use CU\IdCard\{Identity,PortalSessionAdapter};
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode','1');
    if (!session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Lax','cookie_secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off'])) throw new RuntimeException('Session storage unavailable.');
}
/** Identity is resolved from the portal, never a submitted matric or reference. */
function currentStudent(): Identity
{
    $session=$_SESSION;
    $path=getenv('CU_STUDENT_IDENTITY_RESOLVER');
    $bypass=getenv('CU_AUTH_BYPASS')==='1' && in_array($_SERVER['REMOTE_ADDR'] ?? '',['127.0.0.1','::1'],true);
    if ($bypass) {
        // Local-only convenience identity. Ownership remains bound to this one matric.
        $matric=getenv('CU_AUTH_BYPASS_STUDENT_MATRIC') ?: getenv('CU_STUDENT_DEV_MATRIC');
        if (!$matric) throw new RuntimeException('Set CU_AUTH_BYPASS_STUDENT_MATRIC for local student testing.');
        $session['loginid']='auth-bypass';
        $resolver=static fn($login)=>new Identity('DEV_STUDENT:'.$matric,['student'],$matric);
    } elseif ($path) {
        $resolver=require $path;
        if (!$resolver instanceof Closure) throw new RuntimeException('Invalid portal resolver configuration.');
    } else {
        $resolver=static fn($login)=>null;
        // Explicit server-side opt-in, loopback only; never overrides an actual login session.
        if (!isset($session['loginid']) && getenv('CU_STUDENT_DEV_MODE')==='1'
            && in_array($_SERVER['REMOTE_ADDR'] ?? '',['127.0.0.1','::1'],true)
            && ($matric=getenv('CU_STUDENT_DEV_MATRIC'))) {
            $session['loginid']='local-development';
            $resolver=static fn($login)=>new Identity('dev:'.$matric,['student'],$matric);
        }
    }
    $student=(new PortalSessionAdapter($session,$resolver))->current();
    $student->requireRole('student');return $student;
}
function studentCsrfToken(Identity $student): string
{
    $owner=$student->id.'|'.$student->matric;
    if (($_SESSION['idcard_csrf_owner'] ?? null)!==$owner || empty($_SESSION['idcard_csrf'])) {
        $_SESSION['idcard_csrf_owner']=$owner;$_SESSION['idcard_csrf']=bin2hex(random_bytes(32));
    }
    return $_SESSION['idcard_csrf'];
}
function requireStudentCsrf(Identity $student): void
{
    $token=$_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(studentCsrfToken($student),$token)) {
        http_response_code(403);echo json_encode(['success'=>false,'message'=>'Your session token is invalid. Refresh the page and try again.']);exit;
    }
}

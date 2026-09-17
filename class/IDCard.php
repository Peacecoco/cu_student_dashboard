<?php
declare(strict_types=1);
use CU\IdCard\{Identity,Lifecycle};
/** Presentation queries and photo handling only; all transitions use Lifecycle. */
class IDCard extends General
{
    public function __construct(PDO $db,private Lifecycle $lifecycle,private Identity $identity)
    { parent::__construct($db);$identity->requireRole('student'); }
    private function query(string $sql,array $args=[]): PDOStatement
    { $q=$this->db->prepare($sql);$q->execute($args);return $q; }
    public function validateMatric(string $matric): void
    {
        if (trim($matric)!==$this->identity->matric) throw new DomainException('Enter the matriculation number associated with your signed-in student account.');
    }
    public function student(): array
    {
        $a=$this->getApplicantByIdentifier($this->identity->matric);
        if (!$a) throw new DomainException('Student not found. Please contact Student Services.');
        return ['matricnumber'=>$a['identifier'],'name'=>$a['fullname']];
    }
    public function eligibility(string $matric,?string $reason): array
    {
        $this->validateMatric($matric);$this->lifecycle->checkEligibility($this->identity,$reason);
        return ['student'=>$this->student(),'pending'=>$this->pending()];
    }
    public function settings(): array
    { return $this->query('SELECT applicationtype,approvedfee,maxfilesizebytes,allowedphotomimetypes FROM idcardsettings WHERE isactive=1 ORDER BY applicationtype')->fetchAll(); }
    public function applications(): array
    {
        $this->student();
        return $this->query('SELECT a.referencenumber,a.applicationtype,a.paymentstatus,a.status,a.createdat,r.status AS refundstatus FROM idcardapplications a LEFT JOIN idcardrefunds r ON r.applicationid=a.applicationid WHERE a.matricnumber=? ORDER BY a.createdat DESC,a.applicationid DESC',[$this->identity->matric])->fetchAll();
    }
    private function ownedApplication(string $ref): array
    {
        $a=$this->query('SELECT applicationid,referencenumber,paymentstatus,status,paidat FROM idcardapplications WHERE referencenumber=? AND matricnumber=?',[$ref,$this->identity->matric])->fetch();
        if (!$a) throw new DomainException('Application not found.');return $a;
    }
    public function paymentDetails(string $ref): array
    {
        $a=$this->ownedApplication($ref);
        $transactions=$this->query('SELECT paymentreference,gatewayreference,paymentoptionname,baseamount,chargeamount,totalamount,currency,provider,status,paidat,completedat,createdat,failuremessage FROM paymenttransactions WHERE applicationid=? ORDER BY createdat,paymentid',[$a['applicationid']])->fetchAll();
        unset($a['applicationid']);return ['application'=>$a,'transactions'=>$transactions];
    }
    public function history(string $ref): array
    {
        $this->ownedApplication($ref); // Student endpoints stay scoped even if this identity has a staff role too.
        return $this->lifecycle->history($this->identity,$ref);
    }
    private function pending(): ?string
    { return $this->query("SELECT paymentreference FROM idcardpaymentattempts WHERE matricnumber=? AND status='pending' ORDER BY attemptid DESC LIMIT 1",[$this->identity->matric])->fetchColumn() ?: null; }
    public function checkout(string $ref): ?array
    {
        if ($ref==='') { $ref=$this->pending() ?? '';if ($ref==='') return null; }
        $p=$this->query('SELECT p.paymentreference,p.applicationtype,p.baseamount,p.chargeamount,p.totalamount,p.currency,p.provider,p.status,p.createdat,p.completedat,p.failuremessage,a.referencenumber FROM idcardpaymentattempts p LEFT JOIN idcardapplications a ON a.applicationid=p.applicationid WHERE p.paymentreference=? AND p.matricnumber=?',[$ref,$this->identity->matric])->fetch();
        if (!$p) throw new DomainException('Checkout not found.');return $p;
    }
    public function beginCheckout(array $data,array $files): array
    {
        $this->validateMatric((string)($data['matricnumber'] ?? ''));
        $reason=(string)($data['applicationtype'] ?? '');$this->lifecycle->checkEligibility($this->identity,$reason);
        $settings=$this->getIdCardSettings($reason);
        if (!$settings) throw new DomainException('Replacement settings are unavailable.');
        $photo=$this->storePhoto($files['photo'] ?? null,$settings);
        try {
            $ref=$this->lifecycle->beginCheckout($this->identity,$reason,getenv('CU_STUDENT_PAYMENT_OPTION') ?: 'paystack',$photo);
            $stored=$this->query('SELECT photopath FROM idcardpaymentattempts WHERE paymentreference=? AND matricnumber=?',[$ref,$this->identity->matric])->fetchColumn();
            if ($stored!==$photo['path']) unlink(IDCARD_UPLOAD_PATH.'/'.basename($photo['path']));
            return ['paymentreference'=>$ref];
        } catch (Throwable $e) {
            // Only delete this newly staged file when no committed attempt owns it.
            $linked=$this->query('SELECT attemptid FROM idcardpaymentattempts WHERE photopath=? LIMIT 1',[$photo['path']])->fetchColumn();
            if (!$linked && is_file(IDCARD_UPLOAD_PATH.'/'.basename($photo['path']))) unlink(IDCARD_UPLOAD_PATH.'/'.basename($photo['path']));
            throw $e;
        }
    }
    public function cancelCheckout(string $ref): array
    {
        $this->checkout($ref);$this->lifecycle->cancelCheckout($this->identity,$ref);
        return ['message'=>'Checkout cancelled. No replacement application was created.'];
    }
    public function requestRefund(string $ref): array
    {
        $this->lifecycle->requestRefund($this->identity,$ref);
        return ['message'=>'Your refund request has been submitted. The replacement base fee is requested; payment charges are excluded.'];
    }
    private function storePhoto(?array $file,array $settings): array
    {
        if (!$file || !isset($file['tmp_name']) || !is_string($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) throw new DomainException('A passport photo is required.');
        $size=filesize($file['tmp_name']);$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$dimensions=@getimagesize($file['tmp_name']);
        $extensions=['image/jpeg'=>'jpg','image/png'=>'png'];
        if (!$dimensions || !isset($extensions[$mime]) || !in_array($mime,array_map('trim',explode(',',$settings['allowedphotomimetypes'])),true) || $size<=0 || $size>(int)$settings['maxfilesizebytes'] || $dimensions[0]*$dimensions[1]>25000000) throw new DomainException('Upload a valid JPEG or PNG passport photo within the configured size limit.');
        if (!is_dir(IDCARD_UPLOAD_PATH) && !mkdir(IDCARD_UPLOAD_PATH,0775,true) && !is_dir(IDCARD_UPLOAD_PATH)) throw new RuntimeException('Cannot prepare photo folder.');
        $path='uploads/idcard/photo-'.bin2hex(random_bytes(16)).'.'.$extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'],IDCARD_UPLOAD_PATH.'/'.basename($path))) throw new RuntimeException('Cannot store photo.');
        return ['path'=>$path,'size'=>$size,'mime'=>$mime];
    }
}

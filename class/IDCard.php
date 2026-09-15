<?php
class IDCard extends General
{
    public function getApplicationsByIdentifier(string $identifier): never
    {
        $applicant = $this->getApplicantByIdentifier($identifier);
        if (!$applicant) {
            $this->sendResponse(false, 'Student not found. Please provide a valid matriculation number.', null, 404);
        }

        $statement = $this->db->prepare('SELECT applicationid, referencenumber, matricnumber, applicationtype, status, approvedfee, paymentdeadline, createdat FROM idcardapplications WHERE matricnumber = ? ORDER BY createdat DESC');
        $statement->execute([$applicant['identifier']]);
        $applications = $statement->fetchAll();
        if (!$applications) {
            $this->sendResponse(false, 'No applications found for this identifier.', null, 404);
        }

        $this->sendResponse(true, count($applications) . ' application(s) found.', ['applicant' => $applicant, 'applications' => $applications]);
    }

    public function getSettings(?string $applicationType): never
    {
        $query = 'SELECT settingid, applicationtype, approvedfee, expirydays, cooldowndays, maxfilesizebytes, allowedphotomimetypes, alloweddocumentmimetypes, isactive FROM idcardsettings WHERE isactive = 1';
        $parameters = [];
        if ($applicationType) {
            $query .= ' AND applicationtype = ?';
            $parameters[] = $this->sanitize($applicationType);
        }
        $statement = $this->db->prepare($query . ' ORDER BY applicationtype');
        $statement->execute($parameters);
        $settings = $statement->fetchAll();
        if (!$settings) {
            $this->sendResponse(false, 'No active ID card settings found.', null, 404);
        }
        $this->sendResponse(true, count($settings) . ' setting(s) retrieved.', $settings);
    }

    public function submitApplication(array $data, array $files): never
    {
        $required = $this->validateRequired($data, ['applicationtype']);
        $identifier = $this->sanitize($data['matricnumber'] ?? $data['identifier'] ?? '');
        if ($identifier === '') {
            $required[] = 'matricnumber';
        }
        if ($required) {
            $this->sendResponse(false, 'Missing required fields: ' . implode(', ', array_unique($required)), null, 400);
        }
        if (!in_array($data['applicationtype'], ['loststolen', 'damaged'], true)) {
            $this->sendResponse(false, 'Invalid application type.', null, 400);
        }

        $applicant = $this->getApplicantByIdentifier($identifier);
        if (!$applicant) {
            $this->sendResponse(false, 'Student not found. Please provide a valid matriculation number.', null, 404);
        }
        $settings = $this->getIdCardSettings($data['applicationtype']);
        if (!$settings) {
            $this->sendResponse(false, 'No active ID card setting found for this application type.', null, 500);
        }

        $active = $this->db->prepare("SELECT referencenumber FROM idcardapplications WHERE matricnumber = ? AND status IN ('submitted', 'awaitingpayment', 'paid', 'printed', 'readyforpickup', 'acknowledged') LIMIT 1");
        $active->execute([$identifier]);
        if ($active->fetch()) {
            $this->sendResponse(false, 'Applicant already has an active application.', null, 409);
        }

        $cooldownDays = max(0, (int) ($settings['cooldowndays'] ?? 0));
        if ($cooldownDays > 0) {
            $cooldown = $this->db->prepare("SELECT status, COALESCE(closedat, cancelledat, updatedat) AS resolvedat FROM idcardapplications WHERE matricnumber = ? AND status IN ('closed', 'cancelled') AND COALESCE(closedat, cancelledat, updatedat) >= DATE_SUB(NOW(), INTERVAL {$cooldownDays} DAY) ORDER BY createdat DESC LIMIT 1");
            $cooldown->execute([$identifier]);
            if ($row = $cooldown->fetch()) {
                $this->sendResponse(false, 'You cannot reapply yet because a previous application was ' . $row['status'] . ' within the configured cooldown period.', null, 409);
            }
        }

        $photo = $this->storeUpload($files['photo'] ?? null, 'photo', $settings);
        $document = $this->storeUpload($files['document'] ?? ($files['supportingDocument'] ?? null), 'document', $settings);
        $reference = 'IDC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        try {
            $statement = $this->db->prepare('INSERT INTO idcardapplications (referencenumber, matricnumber, applicationtype, status, photopath, photosizebytes, photomimetype, documenttype, documentpath, documentsizebytes, documentmimetype, submittedat, createdat) VALUES (?, ?, ?, "submitted", ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $statement->execute([$reference, $identifier, $data['applicationtype'], $photo['path'], $photo['size'], $photo['mime'], $this->sanitize($document['name']), $document['path'], $document['size'], $document['mime']]);
        } catch (Throwable $exception) {
            @unlink(BASE_PATH . '/' . $photo['path']);
            @unlink(BASE_PATH . '/' . $document['path']);
            $this->sendResponse(false, 'Unable to submit the application.', null, 500);
        }

        $this->sendResponse(true, 'Application submitted successfully.', ['referencenumber' => $reference], 201);
    }

    public function getPaymentInvoice(?string $identifier, ?string $reference): never
    {
        $application = $this->findApplication($identifier, $reference);
        if (!$application) {
            $this->sendResponse(false, 'No matching application was found.', null, 404);
        }
        $this->expireIfPaymentDeadlinePassed($application);
        if ($application['status'] !== 'awaitingpayment') {
            $this->sendResponse(true, 'No pending payment invoice found.', ['application' => $application, 'paymentoptions' => []]);
        }

        $options = $this->getActivePaymentOptions();
        $baseAmount = (float) $application['approvedfee'];
        foreach ($options as &$option) {
            $charge = round(($baseAmount * (float) $option['chargepercentage']) + (float) $option['fixedcharge'], 2);
            $option['chargeamount'] = $charge;
            $option['totalamount'] = round($baseAmount + $charge, 2);
        }
        unset($option);
        $this->sendResponse(true, 'Payment invoice retrieved.', ['application' => $application, 'paymentoptions' => $options]);
    }

    public function getPaymentHistory(?string $identifier, ?string $reference): never
    {
        $application = $this->findApplication($identifier, $reference);
        if (!$application) {
            $this->sendResponse(false, 'No matching application was found.', null, 404);
        }
        $statement = $this->db->prepare('SELECT paymentreference, paymentoptionname, baseamount, chargeamount, totalamount, status, paidat, createdat FROM paymenttransactions WHERE applicationid = ? ORDER BY createdat DESC');
        $statement->execute([$application['applicationid']]);
        $this->sendResponse(true, 'Payment history retrieved.', ['application' => $application, 'transactions' => $statement->fetchAll()]);
    }

    public function processPayment(array $data): never
    {
        $reference = $this->sanitize($data['referencenumber'] ?? '');
        $optionCode = $this->sanitize($data['paymentoptioncode'] ?? '');
        if ($reference === '' || $optionCode === '') {
            $this->sendResponse(false, 'Application reference and payment option are required.', null, 400);
        }
        $application = $this->findApplication(null, $reference);
        if (!$application) {
            $this->sendResponse(false, 'No matching application was found.', null, 404);
        }
        $this->expireIfPaymentDeadlinePassed($application);
        if ($application['status'] !== 'awaitingpayment') {
            $this->sendResponse(false, 'Only applications awaiting payment can be paid.', null, 409);
        }
        $optionStatement = $this->db->prepare('SELECT optioncode, optionname, chargepercentage, fixedcharge FROM paymentoptions WHERE optioncode = ? AND isactive = 1 LIMIT 1');
        $optionStatement->execute([$optionCode]);
        $option = $optionStatement->fetch();
        if (!$option) {
            $this->sendResponse(false, 'The selected payment option is unavailable.', null, 404);
        }

        $baseAmount = (float) $application['approvedfee'];
        $charge = round(($baseAmount * (float) $option['chargepercentage']) + (float) $option['fixedcharge'], 2);
        $total = round($baseAmount + $charge, 2);
        $paymentReference = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        try {
            $this->db->beginTransaction();
            $transaction = $this->db->prepare('INSERT INTO paymenttransactions (applicationid, referencenumber, matricnumber, paymentoptioncode, paymentoptionname, baseamount, chargeamount, totalamount, paymentreference, gatewayreference, status, paidat, createdat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "successful", NOW(), NOW())');
            $transaction->execute([$application['applicationid'], $application['referencenumber'], $application['matricnumber'], $option['optioncode'], $option['optionname'], $baseAmount, $charge, $total, $paymentReference, $this->sanitize($data['gatewayreference'] ?? '') ?: null]);
            $update = $this->db->prepare('UPDATE idcardapplications SET status = "paid", updatedat = NOW() WHERE applicationid = ? AND status = "awaitingpayment"');
            $update->execute([$application['applicationid']]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('The application payment status changed. Please refresh and try again.');
            }
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->sendResponse(false, 'Unable to record payment: ' . $exception->getMessage(), null, 500);
        }
        $this->sendResponse(true, 'Payment recorded successfully. Your application is now awaiting printing.', ['paymentreference' => $paymentReference, 'totalamount' => $total, 'paymentoptionname' => $option['optionname']]);
    }

    private function findApplication(?string $identifier, ?string $reference): ?array
    {
        if ($reference = $this->sanitize($reference ?? '')) {
            $statement = $this->db->prepare('SELECT applicationid, referencenumber, matricnumber, applicationtype, status, approvedfee, paymentdeadline, createdat FROM idcardapplications WHERE referencenumber = ? LIMIT 1');
            $statement->execute([$reference]);
            return $statement->fetch() ?: null;
        }
        $identifier = $this->sanitize($identifier ?? '');
        if ($identifier === '') {
            return null;
        }
        $statement = $this->db->prepare('SELECT applicationid, referencenumber, matricnumber, applicationtype, status, approvedfee, paymentdeadline, createdat FROM idcardapplications WHERE matricnumber = ? ORDER BY createdat DESC LIMIT 1');
        $statement->execute([$identifier]);
        return $statement->fetch() ?: null;
    }

    private function getActivePaymentOptions(): array
    {
        $statement = $this->db->query('SELECT optioncode, optionname, chargepercentage, fixedcharge FROM paymentoptions WHERE isactive = 1 ORDER BY optionname');
        return $statement->fetchAll();
    }

    private function expireIfPaymentDeadlinePassed(array &$application): void
    {
        if ($application['status'] !== 'awaitingpayment' || empty($application['paymentdeadline']) || $application['paymentdeadline'] >= date('Y-m-d')) {
            return;
        }
        $statement = $this->db->prepare('UPDATE idcardapplications SET status = "expired", updatedat = NOW() WHERE applicationid = ? AND status = "awaitingpayment"');
        $statement->execute([$application['applicationid']]);
        $application['status'] = 'expired';
    }

    private function storeUpload(?array $file, string $type, array $settings): array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->sendResponse(false, 'A ' . $type . ' file is required.', null, 400);
        }
        $mime = mime_content_type($file['tmp_name']) ?: '';
        $allowed = $type === 'photo'
            ? explode(',', $settings['allowedphotomimetypes'])
            : explode(',', $settings['alloweddocumentmimetypes']);
        $allowed = array_map('trim', $allowed);
        if (!in_array($mime, $allowed, true) || ($file['size'] ?? 0) > (int) $settings['maxfilesizebytes']) {
            $this->sendResponse(false, 'The uploaded ' . $type . ' does not meet the configured upload rules.', null, 400);
        }
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
        if (!isset($extensions[$mime])) {
            $this->sendResponse(false, 'Unsupported ' . $type . ' file type.', null, 400);
        }
        if (!is_dir(IDCARD_UPLOAD_PATH) && !mkdir(IDCARD_UPLOAD_PATH, 0775, true) && !is_dir(IDCARD_UPLOAD_PATH)) {
            $this->sendResponse(false, 'Unable to prepare the upload folder.', null, 500);
        }
        $filename = $type . '-' . uniqid('', true) . '.' . $extensions[$mime];
        $relativePath = 'uploads/idcard/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], BASE_PATH . '/' . $relativePath)) {
            $this->sendResponse(false, 'Unable to save the uploaded ' . $type . '.', null, 500);
        }
        return ['path' => $relativePath, 'name' => $file['name'], 'size' => (int) $file['size'], 'mime' => $mime];
    }
}

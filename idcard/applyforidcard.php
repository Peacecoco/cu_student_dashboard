<?php
$pageKey = 'apply';
$pageTitle = 'Apply for ID Card';
$pageScript = 'idcard-apply';
require __DIR__ . '/../include/idcard/header.php';
?>
        <header class="topbar"><div><p class="crumb">Student services / ID card</p><h1>Apply for replacement</h1></div></header>
        <section class="panel gate-panel">
            <h2>Find your application record</h2>
            <p>Enter your matriculation number to check eligibility.</p>
            <div class="gate-row"><label>Matriculation number<input id="gateApplicantIdentifier" type="text" placeholder="Enter your matriculation number"></label><button id="gateCheckButton" class="btn primary" type="button">Continue</button></div>
            <div id="eligibilityCard" class="notice"></div>
        </section>
        <section id="applicationFormContainer" class="panel form-panel" hidden>
            <h2>Replacement application</h2>
            <div id="statusMessage" class="notice"></div>
            <div id="settingsNotice" class="notice info">Select a reason to view upload rules and the payment-deadline policy.</div>
            <form id="applyForm" novalidate>
                <label>Matriculation number<input id="applicantIdentifier" type="text" required readonly></label>
                <label>Reason for replacement<select id="applicationType" required><option value="">Select a reason</option><option value="loststolen">Lost / Stolen</option><option value="damaged">Damaged</option></select></label>
                <label>Supporting document type<input id="documentType" type="text" readonly></label>
                <label>Supporting document<input id="supportingDocument" type="file" accept=".pdf,.jpg,.jpeg,.png" required disabled></label>
                <label>New passport photo<input id="passportPhoto" type="file" accept=".jpg,.jpeg,.png" required disabled></label>
                <div id="photoPreviewContainer" class="photo-preview" hidden><img id="photoPreview" alt="Passport photo preview"></div>
                <div class="actions"><button id="submitButton" class="btn primary" type="submit">Submit application</button><button class="btn secondary" type="reset">Clear</button></div>
            </form>
        </section>
    </main>
</div>
<div id="successModal" class="modal" hidden><div class="modal-card"><h2>Application submitted</h2><p id="successMessage"></p><button id="closeSuccessModal" class="btn primary" type="button">Done</button></div></div>
<?php require __DIR__ . '/../include/idcard/footer.php'; ?>

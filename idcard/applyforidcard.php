<?php
$pageKey='apply';$pageTitle='Apply for ID Card';$pageScript='idcard-apply';
require __DIR__.'/../include/idcard/header.php';
?>
<header class="topbar"><p class="crumb">Student services / ID card</p><h1>Apply for replacement</h1><p id="studentIdentity"></p></header>
<section class="panel gate-panel">
    <h2>Check your eligibility</h2><p>Your idcard application records will be used to check whether you can apply for a replacement.</p>
    <form id="gateForm" class="gate-row"><button id="gateCheckButton" class="btn primary" disabled>Continue</button></form>
    <div id="eligibilityCard" class="notice" role="status"></div>
    <a id="resumeCheckout" class="pay-link" hidden>Continue your pending checkout</a>
</section>
<section id="applicationFormContainer" class="panel form-panel" hidden>
    <h2>Replacement details</h2>
    <p>An application is created only after a payment attempt completes.</p>
    <div id="statusMessage" class="notice" role="status"></div>
    <form id="applyForm">
        <label>Matriculation number<input id="applicantIdentifier" readonly required></label>
        <label>Reason for replacement<select id="applicationType" required><option value="">Select a reason</option><option value="loststolen">Lost / Stolen</option><option value="damaged">Damaged / Faded</option></select></label>
        <p id="settingsNotice">Select a reason to view the replacement fee and photo requirements.</p>
        <label>New passport photo<input id="passportPhoto" type="file" accept="image/jpeg,image/png" required></label>
        <div id="photoPreviewContainer" class="photo-preview" hidden><img id="photoPreview" alt="Passport photo preview"></div>
        <div class="actions"><button id="submitButton" class="btn primary">Proceed to checkout</button><button class="btn secondary" type="reset">Clear</button></div>
    </form>
</section>
</main></div>
<?php require __DIR__.'/../include/idcard/footer.php'; ?>

<?php
$pageKey='payment';$pageTitle='Payment Center';$pageScript='idcard-payment';
require __DIR__.'/../include/idcard/header.php';
?>
<header class="topbar"><p class="crumb">Student services / ID card</p><h1>Payment center</h1><p id="studentIdentity"></p></header>
<div id="paymentMessage" class="notice" role="status"></div>
<section id="invoicePanel" class="panel" hidden>
    <h2>Replacement-card checkout</h2>
    <p class="notice info">Local payment simulation. No money is charged and no external provider verifies this payment.</p>
    <div id="invoiceDetails" class="invoice-details"></div>
    <p id="checkoutStatus"></p>
    <div id="paymentActions" class="actions" hidden><button id="payButton" class="btn primary" type="button">Complete simulated payment</button><button id="cancelButton" class="btn secondary" type="button">Cancel checkout</button></div>
</section>
<div class="empty-checkout-actions" aria-label="Payment centre navigation">
    <p>Choose what you would like to do next.</p>
    <div>
        <a class="empty-action secondary-action" href="checkappstatus.php">View Application Requests</a>
        <a class="empty-action primary-action" href="applyforidcard.php">Apply for replacement</a>
    </div>
</div>
</main></div>
<dialog id="payDialog" aria-labelledby="payTitle"><h2 id="payTitle">Complete simulated payment?</h2><p id="payConfirmText"></p><div class="dialog-actions"><button class="btn secondary" type="button" data-close>Go back</button><button id="confirmPay" class="btn primary" type="button">Complete payment</button></div></dialog>
<dialog id="cancelDialog" aria-labelledby="cancelTitle"><h2 id="cancelTitle">Cancel this checkout?</h2><p>If you cancel this payment, your replacement ID-card application will not be initiated. An application is only created after a payment attempt is completed. Do you want to continue?</p><div class="dialog-actions"><button class="btn primary" type="button" data-close>Continue Payment</button><button id="confirmCancel" class="btn secondary" type="button">Cancel Application</button></div></dialog>
<?php require __DIR__.'/../include/idcard/footer.php'; ?>

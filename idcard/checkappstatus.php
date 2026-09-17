<?php
$pageKey='status';$pageTitle='Application Requests';$pageScript='idcard-status';
require __DIR__.'/../include/idcard/header.php';
?>
<header class="topbar"><p class="crumb">Student services / ID card</p><h1>Application Requests</h1><p id="studentIdentity"></p></header>
<div id="statusMessage" class="notice" role="status"></div>
<div class="requests-toolbar"><button id="openRefund" class="btn primary" type="button" disabled>Request Refund</button></div>
<section class="report-table-wrap" aria-label="Your replacement applications">
    <table class="report-table"><thead><tr><th>Reference ID</th><th>Payment Status</th><th>Application History</th><th>Status</th></tr></thead><tbody id="applicationResults"><tr><td colspan="4">Loading your requests...</td></tr></tbody></table>
</section>
</main></div>
<dialog id="paymentDialog" aria-labelledby="paymentTitle"><h2 id="paymentTitle">Payment information</h2><div id="paymentDetails"></div><button class="btn secondary" type="button" data-close>Close</button></dialog>
<dialog id="historyDialog" aria-labelledby="historyTitle"><h2 id="historyTitle">Application history</h2><p id="historyReference"></p><div id="historyDetails"></div><button class="btn secondary" type="button" data-close>Close</button></dialog>
<dialog id="refundDialog" aria-labelledby="refundTitle"><h2 id="refundTitle">Request Refund</h2><p>Refunds cover the replacement base fee only; payment charges are excluded.</p><form id="refundForm"><label>Reference ID<input id="refundReference" required maxlength="50" autocomplete="off"></label><div id="refundMessage" class="notice" role="status"></div><div class="dialog-actions"><button class="btn secondary" type="button" data-close>Cancel</button><button id="submitRefund" class="btn primary">Submit request</button></div></form></dialog>
<?php require __DIR__.'/../include/idcard/footer.php'; ?>

<?php
$pageKey = 'payment';
$pageTitle = 'Payment Center';
$pageScript = 'idcard-payment';
require __DIR__ . '/../include/idcard/header.php';
?>
        <header class="topbar"><p class="crumb">Student services / ID card</p><h1>Payment center</h1></header>
        <section class="panel">
            <h2>Find your payment invoice</h2>
            <div class="gate-row"><label>Application reference<input id="reference" type="text" placeholder="e.g. IDC-20260915-ABCDE"></label><button id="loadButton" class="btn primary" type="button">Load invoice</button></div>
            <div id="paymentMessage" class="notice"></div>
        </section>
        <section id="invoicePanel" class="panel" hidden>
            <h2>Replacement-card payment</h2>
            <div id="invoiceDetails" class="invoice-details"></div>
            <label>Payment gateway<select id="paymentOption"></select></label>
            <button id="payButton" class="btn primary" type="button">Pay</button>
        </section>
        <section id="historyPanel" class="panel" hidden><h2>Payment history</h2><div id="paymentHistory"></div></section>
    </main>
</div>
<?php require __DIR__ . '/../include/idcard/footer.php'; ?>

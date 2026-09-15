<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Center | CU Student</title>
    <link rel="stylesheet" href="../assets/css/idcard-apply.css">
    <link rel="stylesheet" href="../assets/css/idcard-flow.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand"><img src="../assets/images/university-logo.png" alt="Covenant University" onerror="this.style.display='none'"><div><strong>Covenant</strong><span>University</span></div></div>
        <nav><a href="applyforidcard.php">Apply for replacement</a><a href="checkappstatus.php">Application status</a><a class="active" href="paymentcenter.php">Payment</a></nav>
    </aside>
    <main class="content">
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
<script src="../assets/js/idcard-payment.js"></script>
</body>
</html>

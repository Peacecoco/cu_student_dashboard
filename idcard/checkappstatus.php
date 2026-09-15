<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status | CU Student</title>
    <link rel="stylesheet" href="../assets/css/idcard-apply.css">
    <link rel="stylesheet" href="../assets/css/idcard-flow.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand"><img src="../assets/images/university-logo.png" alt="Covenant University" onerror="this.style.display='none'"><div><strong>Covenant</strong><span>University</span></div></div>
        <nav><a href="applyforidcard.php">Apply for replacement</a><a class="active" href="checkappstatus.php">Application status</a><a href="paymentcenter.php">Payment</a></nav>
    </aside>
    <main class="content">
        <header class="topbar"><p class="crumb">Student services / ID card</p><h1>Application status</h1></header>
        <section class="panel">
            <h2>Find your replacement application</h2>
            <div class="gate-row"><label>Matriculation number<input id="identifier" type="text" placeholder="Enter your matriculation number"></label><button id="searchButton" class="btn primary" type="button">Check status</button></div>
            <div id="statusMessage" class="notice"></div>
        </section>
        <section id="applicationResults" class="results" hidden></section>
    </main>
</div>
<script src="../assets/js/idcard-status.js"></script>
</body>
</html>

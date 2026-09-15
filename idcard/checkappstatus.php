<?php
$pageKey = 'status';
$pageTitle = 'Application Status';
$pageScript = 'idcard-status';
require __DIR__ . '/../include/idcard/header.php';
?>
        <header class="topbar"><p class="crumb">Student services / ID card</p><h1>Application status</h1></header>
        <section class="panel">
            <h2>Find your replacement application</h2>
            <div class="gate-row"><label>Matriculation number<input id="identifier" type="text" placeholder="Enter your matriculation number"></label><button id="searchButton" class="btn primary" type="button">Check status</button></div>
            <div id="statusMessage" class="notice"></div>
        </section>
        <section id="applicationResults" class="results" hidden></section>
    </main>
</div>
<?php require __DIR__ . '/../include/idcard/footer.php'; ?>

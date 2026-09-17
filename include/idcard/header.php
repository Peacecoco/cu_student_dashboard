<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | CU Student</title>
    <link rel="stylesheet" href="../assets/css/idcard-apply.css">
    <link rel="stylesheet" href="../assets/css/idcard-flow.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand"><img src="../assets/images/university-logo.png" alt="Covenant University" onerror="this.style.display='none'"><div><strong>Covenant</strong><span>University</span></div></div>
        <nav><?php foreach (['apply' => ['applyforidcard.php', 'Apply for replacement'], 'status' => ['checkappstatus.php', 'Application Requests'], 'payment' => ['paymentcenter.php', 'Payment']] as $key => [$url, $label]): ?><a<?= $pageKey === $key ? ' class="active" aria-current="page"' : '' ?> href="<?= $url ?>"><?= $label ?></a><?php endforeach; ?></nav>
    </aside>
    <main class="content">

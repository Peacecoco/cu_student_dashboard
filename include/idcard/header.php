<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | CU Student</title>
    <link rel="stylesheet" href="../assets/css/idcard-apply.css">
    <?php if ($pageKey !== 'apply'): ?>
    <link rel="stylesheet" href="../assets/css/idcard-flow.css">
    <?php endif; ?>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand"><img src="../assets/images/university-logo.png" alt="Covenant University" onerror="this.style.display='none'"><div><strong>Covenant</strong><span>University</span></div></div>
        <nav><?php foreach (['apply' => ['applyforidcard.php', 'Apply for replacement'], 'status' => ['checkappstatus.php', 'Application status'], 'payment' => ['paymentcenter.php', 'Payment']] as $key => [$url, $label]): ?><a<?= $pageKey === $key ? ' class="active"' : '' ?> href="<?= $url ?>"><?= $label ?></a><?php endforeach; ?></nav>
    </aside>
    <main class="content">

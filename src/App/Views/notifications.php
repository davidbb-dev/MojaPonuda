<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obaveštenja — MojaPonuda</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/catalog.css">
</head>
<body>

<?php require __DIR__ . '/partials/navbar.php'; ?>

<div class="container">
    <h1 class="section-title">🔔 Obaveštenja</h1>
    <?php require __DIR__ . '/partials/flash_message.php'; ?>

    <div class="notif-list">
        <?php foreach ($notifications as $n): ?>
            <?php $href = !empty($n['link']) ? $n['link'] : '#'; ?>
            <a class="notif-item <?= (int)$n['is_read'] === 0 ? 'unread' : '' ?>" href="<?= e($href) ?>">
                <div class="notif-title"><?= e($n['title']) ?></div>
                <div class="notif-body muted"><?= e($n['body']) ?></div>
                <div class="notif-date muted"><?= e(substr((string)$n['created_at'], 0, 16)) ?></div>
            </a>
        <?php endforeach; ?>
        <?php if (empty($notifications)): ?>
            <div class="empty-state">Nemate obaveštenja.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

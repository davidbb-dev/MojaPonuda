<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inbox</title>

    <link rel="stylesheet" href="/css/style.css">
</head>

<body>

<?php require __DIR__ . '/partials/navbar.php'; ?>
<?php require __DIR__ . '/partials/flash_message.php'; ?>

<div class="container">

    <h1 class="page-title">Inbox</h1>

    <?php $conversations = $conversations ?? []; ?>

    <?php if (empty($conversations)): ?>

        <div class="empty-state">
            <div class="empty-title">📭 Nema poruka još</div>
            <div class="empty-subtitle">Kada neko pošalje poruku, pojaviće se ovde.</div>
        </div>

    <?php else: ?>

        <div class="inbox-list">

            <?php foreach ($conversations as $c): ?>

                <a href="/messages/conversation/<?= (int)$c['conversation_id'] ?>" class="inbox-item">

                    <div class="inbox-left">

                        <div class="inbox-title">
                            <?= htmlspecialchars($c['listing_name'] ?? 'Bez naslova') ?>
                        </div>

                        <div class="inbox-preview">
                            <?= htmlspecialchars($c['last_message'] ?? 'Nema poruka') ?>
                        </div>

                    </div>

                    <div class="inbox-right">

                        <?php if (!empty($c['unread_count'])): ?>
                            <span class="unread-badge">
                                <?= (int)$c['unread_count'] ?>
                            </span>
                        <?php endif; ?>

                        <div class="inbox-time">
                            <?= !empty($c['last_message_time'])
                                ? date('d M', strtotime($c['last_message_time']))
                                : '' ?>
                        </div>

                    </div>

                </a>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

</body>
</html>
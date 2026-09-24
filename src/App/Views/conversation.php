<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Conversation</title>

    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/components/message.css">
    <link rel="stylesheet" href="/css/components/conversation.css">

    <script defer src="/js/conversation.js"></script>
</head>

<body>

<?php require __DIR__ . '/partials/navbar.php'; ?>
<?php require __DIR__ . '/partials/flash_message.php'; ?>

<?php $currentUser = (int)($_SESSION['user_id'] ?? 0); ?>

<div class="container">

    <div class="chat-box">

        <!-- HEADER -->
        <div class="chat-header">
            <a href="/messages/inbox" class="back-link">← Inbox</a>
        </div>

        <!-- MESSAGES -->
        <div class="chat-messages" id="chat-messages">

            <?php if (empty($messages)): ?>
                <div class="empty-state">
                    Nema poruka još.
                </div>
            <?php else: ?>

                <?php foreach ($messages as $msg): ?>

                    <?php
                        $isMe = ((int)$msg['sender_user_id'] === $currentUser);
                        $time = !empty($msg['created_at'])
                            ? date('H:i', strtotime($msg['created_at']))
                            : '';
                    ?>

                    <div class="chat-message <?= $isMe ? 'me' : 'other' ?>">

                        <div class="chat-bubble">
                            <?= htmlspecialchars($msg['message'] ?? '') ?>
                        </div>

                        <?php if ($time): ?>
                            <div class="chat-meta">
                                <span class="chat-time"><?= $time ?></span>
                            </div>
                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

        <!-- INPUT -->
        <form class="chat-input"
              method="POST"
              action="/messages/conversation/<?= (int)$conversationId ?>">

            <?= \App\Support\Csrf::field() ?>

            <textarea name="message"
                      placeholder="Napiši poruku..."
                      required></textarea>

            <button type="submit">Pošalji</button>

        </form>

    </div>

</div>

</body>
</html>
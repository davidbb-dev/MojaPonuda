<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Omiljeno — MojaPonuda</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/home.css">
    <link rel="stylesheet" href="/css/catalog.css">
    <script src="/js/partials/countdown.js" defer></script>
</head>
<body>

<?php require __DIR__ . '/partials/navbar.php'; ?>

<div class="container">
    <h1 class="section-title">❤️ Omiljeni oglasi</h1>
    <?php require __DIR__ . '/partials/flash_message.php'; ?>

    <div class="listing-grid">
        <?php foreach ($allListings as $listing): ?>
            <?php require __DIR__ . '/partials/listing_card.php'; ?>
        <?php endforeach; ?>
        <?php if (empty($allListings)): ?>
            <div class="empty-state">Nemate sačuvanih oglasa. Kliknite 🤍 na oglasu da ga sačuvate.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

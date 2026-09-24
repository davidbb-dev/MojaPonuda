<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kupovine i prodaje — MojaPonuda</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/catalog.css">
</head>
<body>

<?php require __DIR__ . '/partials/navbar.php'; ?>

<div class="container">
    <h1 class="section-title">🧾 Moje kupovine i prodaje</h1>
    <?php require __DIR__ . '/partials/flash_message.php'; ?>

    <h2>Kupovine</h2>
    <div class="order-list">
        <?php foreach ($purchases as $o): ?>
            <div class="order-row">
                <img class="order-thumb" src="<?= e($o['image_path'] ?? '/img/placeholder.svg') ?>"
                     onerror="this.onerror=null;this.src='/img/placeholder.svg'" alt="">
                <div class="order-main">
                    <a href="/view/listing/<?= (int)$o['listing_id'] ?>"><strong><?= e($o['listing_name']) ?></strong></a>
                    <p class="muted">Prodavac: <a href="/user/<?= (int)$o['seller_id'] ?>"><?= e($o['seller_name']) ?></a> · <?= e(substr((string)$o['created_at'], 0, 16)) ?></p>
                </div>
                <div class="order-side">
                    <span class="order-price"><?= (int)$o['price'] ?> din</span>
                    <?php if ((int)$o['reviewed'] === 0): ?>
                        <a class="btn-secondary small" href="/view/listing/<?= (int)$o['listing_id'] ?>">Oceni</a>
                    <?php else: ?>
                        <span class="muted small">Ocenjeno ✓</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($purchases)): ?>
            <div class="empty-state">Još nemate kupovina.</div>
        <?php endif; ?>
    </div>

    <h2 style="margin-top:30px">Prodaje</h2>
    <div class="order-list">
        <?php foreach ($sales as $o): ?>
            <div class="order-row">
                <img class="order-thumb" src="<?= e($o['image_path'] ?? '/img/placeholder.svg') ?>"
                     onerror="this.onerror=null;this.src='/img/placeholder.svg'" alt="">
                <div class="order-main">
                    <a href="/view/listing/<?= (int)$o['listing_id'] ?>"><strong><?= e($o['listing_name']) ?></strong></a>
                    <p class="muted">Kupac: <a href="/user/<?= (int)$o['buyer_id'] ?>"><?= e($o['buyer_name']) ?></a> · <?= e(substr((string)$o['created_at'], 0, 16)) ?></p>
                </div>
                <div class="order-side">
                    <span class="order-price"><?= (int)$o['price'] ?> din</span>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($sales)): ?>
            <div class="empty-state">Još nemate prodaja.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

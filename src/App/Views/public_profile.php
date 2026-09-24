<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($profile['username']) ?> — MojaPonuda</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/home.css">
    <link rel="stylesheet" href="/css/catalog.css">
    <script src="/js/partials/countdown.js" defer></script>
</head>
<body>

<?php require __DIR__ . '/partials/navbar.php'; ?>

<?php
    // $rating arrives as ['avg' => float, 'count' => int] from the controller.
    $ratingAvg = (float)($rating['avg'] ?? 0);
    $ratingCount = (int)($rating['count'] ?? 0);
?>

<div class="container">

    <div class="profile-header">
        <?php if (!empty($profile['avatar_path'])): ?>
            <img class="profile-avatar" src="<?= e($profile['avatar_path']) ?>" alt="">
        <?php else: ?>
            <span class="profile-avatar placeholder">👤</span>
        <?php endif; ?>
        <div>
            <h1><?= e($profile['username']) ?></h1>
            <div class="profile-rating">
                <?php $rating = $ratingAvg; $count = $ratingCount; require __DIR__ . '/partials/stars.php'; ?>
            </div>
            <p class="muted">Član od <?= e(substr((string)$profile['created_at'], 0, 10)) ?> · <?= (int)$profile['active_listings'] ?> aktivnih oglasa</p>
            <?php if (!empty($profile['bio'])): ?>
                <p><?= nl2br(e($profile['bio'])) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <h2 class="section-title">Aktivni oglasi</h2>
    <div class="listing-grid">
        <?php foreach ($listings as $listing): ?>
            <?php require __DIR__ . '/partials/listing_card.php'; ?>
        <?php endforeach; ?>
        <?php if (empty($listings)): ?>
            <div class="empty-state">Ovaj korisnik trenutno nema aktivnih oglasa.</div>
        <?php endif; ?>
    </div>

    <?php if (!empty($reviews)): ?>
        <h2 class="section-title">Recenzije</h2>
        <div class="reviews-section">
            <?php unset($count); ?>
            <?php foreach ($reviews as $rev): ?>
                <div class="review-item">
                    <div class="review-head">
                        <strong><?= e($rev['reviewer_name']) ?></strong>
                        <?php $rating = (float)$rev['rating']; require __DIR__ . '/partials/stars.php'; ?>
                        <span class="review-date"><?= e($rev['created_at']) ?></span>
                    </div>
                    <?php if (!empty($rev['comment'])): ?>
                        <p><?= nl2br(e($rev['comment'])) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>

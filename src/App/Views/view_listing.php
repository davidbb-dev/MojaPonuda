<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($listing['name'] ?? 'Oglas') ?> — MojaPonuda</title>

    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/view_listing.css">
    <link rel="stylesheet" href="/css/components/modal.css">
    <link rel="stylesheet" href="/css/components/message.css">

    <script type="module" src="/js/view_listing.js" defer></script>
    <script type="module" src="/js/listing_actions.js" defer></script>
    <?php if (($listing['listing_type'] ?? '') === 'auction'): ?>
        <script src="/js/partials/countdown.js" defer></script>
    <?php endif; ?>
</head>
<body>

<?php require __DIR__ . '/partials/navbar.php'; ?>
<?php require __DIR__ . '/partials/confirmModal.php'; ?>
<?php require __DIR__ . '/partials/message.php'; ?>

<?php
    $userId = $_SESSION['user_id'] ?? null;
    $isOwner = $userId && (int)$userId === (int)$listing['user_id'];
    $images = $listing['images'] ?? [];
    $mainImage = !empty($images[0]['image_path']) ? $images[0]['image_path'] : '/img/placeholder.svg';
?>

<div class="container">

    <?php require __DIR__ . '/partials/flash_message.php'; ?>

    <div class="listing-card-large">

        <div class="listing-media">
            <img id="main-image" class="listing-image"
                 src="<?= e($mainImage) ?>"
                 alt="<?= e($listing['name'] ?? '') ?>"
                 onerror="this.onerror=null;this.src='/img/placeholder.svg'">

            <?php if (count($images) > 1): ?>
                <div class="thumb-row">
                    <?php foreach ($images as $img): ?>
                        <img class="thumb" src="<?= e($img['image_path']) ?>" alt=""
                             onclick="document.getElementById('main-image').src=this.src">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="listing-content">

            <div class="title-row">
                <h1 class="listing-title"><?= e($listing['name'] ?? '') ?></h1>
                <?php if ($userId): ?>
                    <button id="btn-favorite"
                            class="favorite-btn <?= $isFavorited ? 'is-fav' : '' ?>"
                            data-listing-id="<?= (int)$listing['listing_id'] ?>"
                            title="Dodaj u omiljeno">
                        <?= $isFavorited ? '❤️' : '🤍' ?>
                    </button>
                <?php endif; ?>
            </div>

            <p class="listing_type_label <?= e($listing['listing_type']) ?>">
                <?= $listing['listing_type'] === 'auction' ? '🔨 AUKCIJA' : '🛒 KUPI ODMAH' ?>
            </p>

            <div class="listing-price-box">
                <span class="listing-price"><?= (int)($listing['current_price'] ?? 0) ?> din</span>
            </div>

            <?php if ($listing['status'] === 'sold'): ?>
                <p class="sold-banner">✅ Prodato</p>
            <?php endif; ?>

            <?php if (($listing['listing_type'] ?? '') === 'auction'): ?>
                <?php if (!empty($listing['ended_at'])): ?>
                    <div class="time-left" data-ended-at="<?= e($listing['ended_at']) ?>"
                         data-status="<?= e($listing['status']) ?>">Učitavanje...</div>
                <?php endif; ?>

                <?php if ($userId && !$isOwner && $listing['status'] === 'active'): ?>
                    <div class="auction-box">
                        <input id="input-auction-bid" type="number" class="auction-input"
                               placeholder="Min: <?= (int)(($listing['current_price'] ?? 0) + AUCTION_MIN_BID_INCREMENT) ?> din">
                        <button id="btn-auction-bid"
                                data-listing-id="<?= (int)$listing['listing_id'] ?>"
                                class="btn-primary">Licitiraj</button>
                    </div>
                <?php endif; ?>

                <button id="btn-bid-history" class="btn-secondary"
                        data-listing-id="<?= (int)$listing['listing_id'] ?>">
                    <?= (int)($bidsData['bid_count'] ?? 0) ?> ponuda — istorija
                </button>
                <div id="bid-history" class="bid-history" hidden></div>

            <?php else: ?>
                <?php if (!$userId): ?>
                    <a href="/login/identifier" class="btn-primary">Prijavite se za kupovinu</a>
                <?php elseif (!$isOwner && $listing['status'] === 'active'): ?>
                    <button id="btn-buy-now" class="btn-primary buy-now"
                            data-listing-id="<?= (int)$listing['listing_id'] ?>">
                        🛒 Kupi odmah
                    </button>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($userId && !$isOwner): ?>
                <?php if (!empty($conversationId)): ?>
                    <a href="/messages/conversation/<?= (int)$conversationId ?>" class="btn-secondary">Nastavi razgovor</a>
                <?php else: ?>
                    <form method="POST" action="/messages/conversation/create">
                        <?= \App\Support\Csrf::field() ?>
                        <input type="hidden" name="listing_id" value="<?= (int)$listing['listing_id'] ?>">
                        <button type="submit" class="btn-secondary">Kontaktiraj prodavca</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($seller): ?>
                <div class="seller-card">
                    <a href="/user/<?= (int)$seller['user_id'] ?>" class="seller-link">
                        <?php if (!empty($seller['avatar_path'])): ?>
                            <img class="seller-avatar" src="<?= e($seller['avatar_path']) ?>" alt="">
                        <?php else: ?>
                            <span class="seller-avatar placeholder">👤</span>
                        <?php endif; ?>
                        <span>
                            <strong><?= e($seller['username']) ?></strong><br>
                            <?php $rating = $sellerRating['avg']; $count = $sellerRating['count'];
                                  require __DIR__ . '/partials/stars.php'; ?>
                        </span>
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="listing-description">
        <h2>Opis oglasa</h2>
        <p><?= nl2br(e($listing['description'] ?? '')) ?></p>
    </div>

    <!-- Review form (only the buyer of a sold item can review) -->
    <?php if ($canReview): ?>
        <div class="review-form-card">
            <h2>Ocenite prodavca</h2>
            <form method="POST" action="/reviews">
                <?= \App\Support\Csrf::field() ?>
                <input type="hidden" name="listing_id" value="<?= (int)$listing['listing_id'] ?>">
                <label>Ocena:
                    <select name="rating" required>
                        <option value="5">★★★★★ (5)</option>
                        <option value="4">★★★★ (4)</option>
                        <option value="3">★★★ (3)</option>
                        <option value="2">★★ (2)</option>
                        <option value="1">★ (1)</option>
                    </select>
                </label>
                <textarea name="comment" maxlength="1000" placeholder="Vaš komentar (opciono)..."></textarea>
                <button type="submit" class="btn-primary">Pošalji recenziju</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Seller reviews -->
    <?php if (!empty($sellerReviews)): ?>
        <div class="reviews-section">
            <h2>Recenzije prodavca</h2>
            <?php unset($count); // per-review stars show no count ?>
            <?php foreach ($sellerReviews as $rev): ?>
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
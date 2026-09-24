<?php
/**
 * Reusable listing card.
 * Expects $listing with: listing_id, name, current_price, listing_type,
 * image_path (nullable), ended_at (nullable), is_featured (optional).
 */
$cardImg = !empty($listing['image_path']) ? $listing['image_path'] : '/img/placeholder.svg';
?>
<a class="listing-card" href="/view/listing/<?= (int)$listing['listing_id'] ?>">
    <div class="listing-img-wrap">
        <img class="listing-img"
             src="<?= e($cardImg) ?>"
             alt="<?= e($listing['name']) ?>"
             loading="lazy"
             onerror="this.onerror=null;this.src='/img/placeholder.svg'">
        <?php if (!empty($listing['is_featured'])): ?>
            <span class="featured-badge">★ Izdvojeno</span>
        <?php endif; ?>
    </div>
    <div class="listing-info">
        <h3 class="listing-title"><?= e($listing['name']) ?></h3>
        <p class="listing-price"><?= (int)($listing['current_price'] ?? 0) ?> din</p>
        <p class="listing_type_label <?= e($listing['listing_type']) ?>">
            <?= ($listing['listing_type'] ?? '') === 'auction' ? '🔨 AUKCIJA' : '🛒 KUPI ODMAH' ?>
        </p>
        <?php if (($listing['listing_type'] ?? '') === 'auction' && !empty($listing['ended_at'])): ?>
            <div class="time-left"
                 data-ended-at="<?= e($listing['ended_at']) ?>"
                 data-status="active">Učitavanje...</div>
        <?php endif; ?>
    </div>
</a>

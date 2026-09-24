<?php
/**
 * Renders star rating. Expects $rating (float 0-5). Optional $count.
 */
$full = (int)floor($rating);
$half = ($rating - $full) >= 0.5;
$empty = 5 - $full - ($half ? 1 : 0);
?>
<span class="stars" title="<?= number_format((float)$rating, 1) ?> / 5">
    <?= str_repeat('★', max(0, $full)) ?><?= $half ? '⯨' : '' ?><span class="stars-empty"><?= str_repeat('★', max(0, $empty)) ?></span>
    <?php if (isset($count)): ?>
        <span class="stars-count">(<?= (int)$count ?>)</span>
    <?php endif; ?>
</span>

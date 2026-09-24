<?php
/**
 * Pagination. Expects: $page, $totalPages, $basePath, $baseParams (array).
 */
if (($totalPages ?? 1) <= 1) {
    return;
}

$buildUrl = function (int $p) use ($basePath, $baseParams): string {
    $params = $baseParams;
    $params['page'] = $p;
    return $basePath . '?' . http_build_query($params);
};

$start = max(1, $page - 2);
$end = min($totalPages, $page + 2);
?>
<nav class="pagination">
    <?php if ($page > 1): ?>
        <a class="page-link" href="<?= e($buildUrl($page - 1)) ?>">‹ Prethodna</a>
    <?php endif; ?>

    <?php if ($start > 1): ?>
        <a class="page-link" href="<?= e($buildUrl(1)) ?>">1</a>
        <?php if ($start > 2): ?><span class="page-gap">…</span><?php endif; ?>
    <?php endif; ?>

    <?php for ($p = $start; $p <= $end; $p++): ?>
        <a class="page-link <?= $p === $page ? 'active' : '' ?>" href="<?= e($buildUrl($p)) ?>"><?= $p ?></a>
    <?php endfor; ?>

    <?php if ($end < $totalPages): ?>
        <?php if ($end < $totalPages - 1): ?><span class="page-gap">…</span><?php endif; ?>
        <a class="page-link" href="<?= e($buildUrl($totalPages)) ?>"><?= $totalPages ?></a>
    <?php endif; ?>

    <?php if ($page < $totalPages): ?>
        <a class="page-link" href="<?= e($buildUrl($page + 1)) ?>">Sledeća ›</a>
    <?php endif; ?>
</nav>

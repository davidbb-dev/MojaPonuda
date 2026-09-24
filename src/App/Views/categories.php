<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategorije — MojaPonuda</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/catalog.css">
</head>
<body>

<?php require __DIR__ . '/partials/navbar.php'; ?>

<div class="container">
    <h1 class="section-title">Sve kategorije</h1>
    <?php require __DIR__ . '/partials/flash_message.php'; ?>

    <div class="category-grid">
        <?php foreach ($categories as $cat): ?>
            <a class="category-card" href="/category/<?= (int)$cat['category_id'] ?>">
                <h3><?= e($cat['name']) ?></h3>
                <p class="muted"><?= e($cat['description']) ?></p>
                <span class="category-count"><?= (int)$cat['listing_count'] ?> aktivnih oglasa</span>
            </a>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?>
            <div class="empty-state">Još uvek nema kategorija.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

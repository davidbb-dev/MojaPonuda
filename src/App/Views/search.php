<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($heading) ?> — MojaPonuda</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/home.css">
    <link rel="stylesheet" href="/css/catalog.css">
    <script src="/js/partials/countdown.js" defer></script>
</head>
<body>

<?php require __DIR__ . '/partials/navbar.php'; ?>

<div class="container">

    <h1 class="section-title"><?= e($heading) ?></h1>
    <?php require __DIR__ . '/partials/flash_message.php'; ?>

    <form class="filter-bar" action="<?= e($basePath) ?>" method="GET">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Ključna reč...">

        <?php if ($forcedCategoryId === null): ?>
            <select name="category">
                <option value="">Sve kategorije</option>
                <?php foreach ($allCategories as $cat): ?>
                    <option value="<?= (int)$cat['category_id'] ?>" <?= ($categoryId === (int)$cat['category_id']) ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <select name="type">
            <option value="">Svi tipovi</option>
            <option value="fixed_price" <?= $type === 'fixed_price' ? 'selected' : '' ?>>Kupi odmah</option>
            <option value="auction" <?= $type === 'auction' ? 'selected' : '' ?>>Aukcija</option>
        </select>

        <input type="number" name="min_price" value="<?= $minPrice !== null ? (int)$minPrice : '' ?>" placeholder="Min cena" min="0">
        <input type="number" name="max_price" value="<?= $maxPrice !== null ? (int)$maxPrice : '' ?>" placeholder="Max cena" min="0">

        <select name="sort">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Najnovije</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Najstarije</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Cena ↑</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Cena ↓</option>
            <option value="ending_soon" <?= $sort === 'ending_soon' ? 'selected' : '' ?>>Uskoro ističe</option>
        </select>

        <button type="submit" class="btn-primary">Primeni</button>
    </form>

    <p class="result-count"><?= (int)$total ?> rezultata</p>

    <div class="listing-grid">
        <?php foreach ($listings as $listing): ?>
            <?php require __DIR__ . '/partials/listing_card.php'; ?>
        <?php endforeach; ?>
        <?php if (empty($listings)): ?>
            <div class="empty-state">Nema oglasa koji odgovaraju pretrazi.</div>
        <?php endif; ?>
    </div>

    <?php require __DIR__ . '/partials/pagination.php'; ?>

</div>

</body>
</html>

<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MojaPonuda — kupovina i aukcije</title>

    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/home.css">

    <script src="/js/partials/countdown.js" defer></script>
</head>
<body>

    <?php require __DIR__.'/partials/navbar.php'; ?>

    <section class="hero">
        <div class="hero-inner">
            <h1>Pronađi šta tražiš. Prodaj šta ne koristiš.</h1>
            <p>Hiljade oglasa i aukcija na jednom mestu.</p>
            <form class="hero-search" action="/search" method="GET">
                <input type="text" name="q" placeholder="Šta tražite danas?">
                <button type="submit">Pretraži</button>
            </form>
        </div>
    </section>

    <div class="container">

        <?php require __DIR__.'/partials/flash_message.php'; ?>

        <?php if (!empty($recentlyViewed)): ?>
            <h2 class="section-title">🕘 Nedavno gledano</h2>
            <div class="listing-grid">
                <?php foreach ($recentlyViewed as $listing): ?>
                    <?php require __DIR__.'/partials/listing_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($featuredListings)): ?>
            <h2 class="section-title">⭐ Izdvojeni oglasi</h2>
            <div class="listing-grid">
                <?php foreach ($featuredListings as $listing): ?>
                    <?php require __DIR__.'/partials/listing_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h2 class="section-title">🛒 Najnoviji oglasi</h2>
        <div class="listing-grid">
            <?php foreach ($fixedPriceListings as $listing): ?>
                <?php require __DIR__.'/partials/listing_card.php'; ?>
            <?php endforeach; ?>
            <?php if (empty($fixedPriceListings)): ?>
                <p class="muted">Trenutno nema oglasa.</p>
            <?php endif; ?>
        </div>

        <h2 class="section-title">🔨 Najnovije aukcije</h2>
        <div class="listing-grid">
            <?php foreach ($auctionListings as $listing): ?>
                <?php require __DIR__.'/partials/listing_card.php'; ?>
            <?php endforeach; ?>
            <?php if (empty($auctionListings)): ?>
                <p class="muted">Trenutno nema aktivnih aukcija.</p>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>

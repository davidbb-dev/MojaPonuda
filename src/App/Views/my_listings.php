<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moji oglasi i aukcije</title>
    
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/my_listings.css">
    <link rel="stylesheet" href="/css/components/modal.css">
    <link rel="stylesheet" href="/css/components/message.css">
    

    <script src="/js/partials/countdown.js" defer></script>
    <script src="/js/partials/listings-filter.js" defer></script>
    <script type="module" src="/js/my_listings.js" defer></script>
</head>
<body>
    
    <?php require __DIR__.'/partials/navbar.php'; ?>
    <?php require __DIR__.'/partials/confirmModal.php'; ?>
    <?php require __DIR__.'/partials/message.php'; ?>

    <div class="container">

        <h1>Moji oglasi i aukcije</h1>

        <div class="listings-control">

            <input type="text" id="searchInput" placeholder="Pretraga...">

            <select id="statusFilter">
                <option value="all">Svi statusi</option>
                <option value="active">Aktivni</option>
                <option value="draft">Draft</option>
                <option value="paused">Pauzirani</option>
                <option value="sold">Prodati</option>
                <option value="expired">Istekli</option>
            </select>

            <select id="typeFilter">
                <option value="all">Tip oglasa</option>
                <option value="auction">Aukcija</option>
                <option value="fixed_price">Kupi Odmah</option>
            </select>
         </div>

         <div class="listing_wrapper">

            <?php foreach($allListings as $listing): ?>
                
                <?php
                    $status = $listing['status'] ?? 'active';

                    $statusLabels = [
                        'active' => ['label' => 'Aktivan', 'icon' => '🟢'],
                        'draft' => ['label' => 'Draft', 'icon' => '📝'],
                        'paused' => ['label' => 'Pauziran', 'icon' => '⏸️'],
                        'sold' => ['label' => 'Prodat', 'icon' => '✅'],
                        'expired' => ['label' => 'Istekao', 'icon' => '⛔'],
                    ];
                    
                    $badge = $statusLabels[$status] ?? $statusLabels['active'];
                ?>

                <div 
                    class="listing_container status-<?= htmlspecialchars($listing['status'] ?? 'active') ?>"
                    data-id="<?= htmlspecialchars($listing['listing_id']) ?>"
                    data-status="<?= htmlspecialchars($listing['status'] ?? 'active') ?>"
                    data-type="<?= htmlspecialchars($listing['listing_type'] ?? 'fixed_price') ?>"
                    data-name="<?= htmlspecialchars($listing['name']) ?>"
                >

                    <div class="image_wrapper">
                        <img
                            src="<?= htmlspecialchars($listing['image_path'] ?? '/img/placeholder.svg', ENT_QUOTES, 'UTF-8') ?>"
                            alt=""
                            class="img_listing"
                            onerror="this.onerror=null;this.src='/img/placeholder.svg'"
                        >
                    </div>

                    <div class="info_wrapper">

                        <h2 class="h2-name">
                            <?= htmlspecialchars($listing['name']) ?>
                        </h2>

                        <p class="current_price">
                            <?= htmlspecialchars((string)$listing['current_price']) ?> din
                        </p>

                        <span class="status-badge status-<?= htmlspecialchars($status) ?>">
                            <?= $badge['icon'] ?> <?= $badge['label'] ?>
                        </span>

                        <p class="listing_type_label <?= htmlspecialchars($listing['listing_type']) ?>">
                            <?= $listing['listing_type'] === 'auction' ? '🔨 AUKCIJA' : '🛒 KUPI ODMAH' ?>
                        </p>

                        <?php if($listing['listing_type'] === 'auction'): ?>
                            <div 
                                class="time-left"
                                data-ended-at="<?= htmlspecialchars($listing['ended_at']) ?>"
                                data-status="<?= htmlspecialchars($listing['status'] ?? 'active') ?>"
                            >
                                Učitavanje...
                            </div>
                        <?php endif; ?>
                        
                    </div>

                    <div class="buttons_wrapper">

                            <a href="/edit/listing/<?= $listing['listing_id'] ?>" class="listing_button">
                                Izmeni
                            </a>

                            <button 
                                class="listing_button btn_pause_listing" 
                                data-listing-id="<?= $listing['listing_id'] ?>" 
                            >
                                Učitavanje...
                            </button>

                            <button 
                                class="listing_button btn_delete_listing" 
                                data-listing-id="<?= $listing['listing_id'] ?>"
                            >
                                Obriši
                            </button>

                    </div>

                </div>

            <?php endforeach; ?>

         </div>  

    </div>
    
    <?php if(!empty($flashMessage)): ?>
        <div class="flash-message"><?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
</body>
</html>
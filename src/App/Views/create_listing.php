<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($form['text']['title'] ?? 'Oglas') ?></title>

    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/create_listing.css">
    <link rel="stylesheet" href="/css/create_listing_validation.css">

    <script> 
        window.existingImages = <?= json_encode($form['values']['images'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
    </script>

    <script src="/js/create_listing.js" defer></script>
</head>
<body>
    <?php require __DIR__.'/partials/navbar.php'; ?>

    <div class="container">

        <div class="page-header">
            <h1><?= htmlspecialchars($form['text']['h1'] ?? 'Oglas') ?></h1>
        </div>
        
        <?php require __DIR__.'/partials/flash_message.php'; ?>  
         
        <div class="form-card">
            <?php require __DIR__.'/partials/listing_form.php'; ?>
        </div>  
        
    </div>
</body>
</html>
<?php
/** Expects: $active (string), $admin (array). */
$nav = [
    'dashboard'  => ['/admin', '📊 Kontrolna tabla'],
    'users'      => ['/admin/users', '👥 Korisnici'],
    'listings'   => ['/admin/listings', '📦 Oglasi'],
    'categories' => ['/admin/categories', '🗂️ Kategorije'],
    'reviews'    => ['/admin/reviews', '⭐ Recenzije'],
    'logs'       => ['/admin/logs', '📜 Logovi (baza)'],
    'filelog'    => ['/admin/file-log', '📄 Pristupni log (fajl)'],
];
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — MojaPonuda</title>
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a href="/" class="admin-brand">MojaPonuda<span>admin</span></a>
        <nav class="admin-nav">
            <?php foreach ($nav as $key => [$href, $label]): ?>
                <a href="<?= $href ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-sidebar-foot">
            <span class="admin-who"><?= e($admin['username']) ?> · <?= e($admin['role']) ?></span>
            <a href="/" class="admin-back">← Nazad na sajt</a>
            <a href="/logout" class="admin-back">Odjava</a>
        </div>
    </aside>
    <main class="admin-main">
        <?php if (!empty($flashMessage)): ?>
            <div class="admin-flash"><?= e($flashMessage) ?></div>
        <?php endif; ?>

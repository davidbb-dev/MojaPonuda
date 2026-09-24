<?php require __DIR__ . '/partials/header.php'; ?>

<h1>Kontrolna tabla</h1>

<div class="stat-grid">
    <div class="stat-card"><span class="stat-num"><?= (int)$stats['users'] ?></span><span class="stat-label">Korisnika</span></div>
    <div class="stat-card"><span class="stat-num"><?= (int)$stats['listings'] ?></span><span class="stat-label">Oglasa</span></div>
    <div class="stat-card"><span class="stat-num"><?= (int)$stats['active'] ?></span><span class="stat-label">Aktivnih</span></div>
    <div class="stat-card"><span class="stat-num"><?= (int)$stats['auctions'] ?></span><span class="stat-label">Aktivnih aukcija</span></div>
    <div class="stat-card"><span class="stat-num"><?= (int)$stats['sold'] ?></span><span class="stat-label">Prodato</span></div>
    <div class="stat-card"><span class="stat-num"><?= (int)$stats['bids'] ?></span><span class="stat-label">Ponuda</span></div>
    <div class="stat-card"><span class="stat-num"><?= (int)$stats['orders'] ?></span><span class="stat-label">Porudžbina</span></div>
    <div class="stat-card"><span class="stat-num"><?= (int)$stats['reviews'] ?></span><span class="stat-label">Recenzija</span></div>
    <div class="stat-card"><span class="stat-num"><?= (int)$stats['categories'] ?></span><span class="stat-label">Kategorija</span></div>
    <div class="stat-card"><span class="stat-num"><?= (int)$stats['blocked'] ?></span><span class="stat-label">Blokiranih</span></div>
    <div class="stat-card wide"><span class="stat-num"><?= number_format((float)$stats['revenue'], 0, ',', '.') ?> din</span><span class="stat-label">Ukupan promet</span></div>
</div>

<h2>Poslednje aktivnosti</h2>
<table class="admin-table">
    <thead><tr><th>Korisnik</th><th>Akcija</th><th>IP</th><th>Vreme</th></tr></thead>
    <tbody>
    <?php foreach ($recentLogs as $log): ?>
        <tr>
            <td><?= e($log['username'] ?? '—') ?></td>
            <td><?= e($log['action']) ?></td>
            <td><?= e($log['ip_address'] ?? '—') ?></td>
            <td><?= e($log['created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($recentLogs)): ?>
        <tr><td colspan="4" class="muted">Nema zapisa.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/partials/footer.php'; ?>

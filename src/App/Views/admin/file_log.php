<?php require __DIR__ . '/partials/header.php'; ?>

<h1>Pristupni log (tekstualni fajl)</h1>
<p class="muted">Sadržaj se čita iz fajla <code>storage/logs/access.log</code> na serveru.</p>

<div class="stat-grid">
    <div class="stat-card"><span class="stat-num"><?= (int)$fileStats['total'] ?></span><span class="stat-label">Ukupno zapisa</span></div>
    <div class="stat-card"><span class="stat-num"><?= (int)$fileStats['log_in'] ?></span><span class="stat-label">Prijava</span></div>
    <div class="stat-card"><span class="stat-num"><?= (int)$fileStats['log_out'] ?></span><span class="stat-label">Odjava</span></div>
    <div class="stat-card"><span class="stat-num"><?= (int)$fileStats['register'] ?></span><span class="stat-label">Registracija</span></div>
</div>

<h2>Sadržaj fajla</h2>
<?php if ($logContent === ''): ?>
    <div class="empty-state">Fajl je prazan ili još ne postoji. Prijavite se/odjavite da generišete zapise.</div>
<?php else: ?>
    <textarea class="file-log-view" readonly rows="22"><?= e($logContent) ?></textarea>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

<?php require __DIR__ . '/partials/header.php'; ?>

<h1>Logovi aktivnosti</h1>

<table class="admin-table">
    <thead><tr><th>ID</th><th>Korisnik</th><th>Akcija</th><th>IP adresa</th><th>Vreme</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $log): ?>
        <tr>
            <td><?= (int)$log['log_id'] ?></td>
            <td><?= e($log['username'] ?? '—') ?></td>
            <td><?= e($log['action']) ?></td>
            <td><?= e($log['ip_address'] ?? '—') ?></td>
            <td><?= e($log['created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($logs)): ?>
        <tr><td colspan="5" class="muted">Nema zapisa.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/partials/footer.php'; ?>
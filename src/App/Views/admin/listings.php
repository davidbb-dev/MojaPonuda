<?php require __DIR__ . '/partials/header.php'; ?>

<h1>Oglasi</h1>

<form class="admin-search" method="GET" action="/admin/listings">
    <input type="text" name="q" placeholder="Pretraga (naziv, prodavac)..." value="<?= e($_GET['q'] ?? '') ?>">
    <select name="status">
        <option value="">Svi statusi</option>
        <?php foreach (['active', 'paused', 'draft', 'sold', 'expired'] as $st): ?>
            <option value="<?= $st ?>" <?= ($_GET['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-primary">Traži</button>
</form>

<table class="admin-table">
    <thead>
        <tr><th>ID</th><th>Naziv</th><th>Prodavac</th><th>Cena</th><th>Tip</th><th>Status</th><th>Akcije</th></tr>
    </thead>
    <tbody>
    <?php foreach ($listings as $l): ?>
        <tr>
            <td><?= (int)$l['listing_id'] ?></td>
            <td>
                <a href="/view/listing/<?= (int)$l['listing_id'] ?>"><?= e($l['name']) ?></a>
                <?php if ((int)$l['is_featured'] === 1): ?><span class="badge badge-gold">★</span><?php endif; ?>
            </td>
            <td><a href="/user/<?= (int)$l['owner_id'] ?>"><?= e($l['owner']) ?></a></td>
            <td><?= (int)$l['current_price'] ?> din</td>
            <td><?= e($l['listing_type']) ?></td>
            <td><span class="badge"><?= e($l['status']) ?></span></td>
            <td class="actions">
                <form method="POST" action="/admin/listings/feature" class="inline-form">
                    <?= \App\Support\Csrf::field() ?>
                    <input type="hidden" name="listing_id" value="<?= (int)$l['listing_id'] ?>">
                    <button class="btn-secondary small"><?= (int)$l['is_featured'] === 1 ? 'Skloni isticanje' : 'Istakni' ?></button>
                </form>
                <form method="POST" action="/admin/listings/delete" class="inline-form"
                      onsubmit="return confirm('Ukloniti ovaj oglas?');">
                    <?= \App\Support\Csrf::field() ?>
                    <input type="hidden" name="listing_id" value="<?= (int)$l['listing_id'] ?>">
                    <button class="btn-danger small">Ukloni</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($listings)): ?>
        <tr><td colspan="7" class="muted">Nema oglasa.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/partials/footer.php'; ?>

<?php require __DIR__ . '/partials/header.php'; ?>

<h1>Kategorije</h1>

<div class="form-card admin-add">
    <h2>Dodaj kategoriju</h2>
    <form method="POST" action="/admin/categories/create">
        <?= \App\Support\Csrf::field() ?>
        <input type="text" name="name" placeholder="Naziv" required>
        <input type="text" name="description" placeholder="Opis" required>
        <button class="btn-primary">Dodaj</button>
    </form>
</div>

<table class="admin-table">
    <thead><tr><th>ID</th><th>Naziv</th><th>Opis</th><th>Oglasa</th><th>Akcije</th></tr></thead>
    <tbody>
    <?php foreach ($categories as $c): ?>
        <tr>
            <td><?= (int)$c['category_id'] ?></td>
            <td colspan="2">
                <form method="POST" action="/admin/categories/update" class="inline-form edit-cat">
                    <?= \App\Support\Csrf::field() ?>
                    <input type="hidden" name="category_id" value="<?= (int)$c['category_id'] ?>">
                    <input type="text" name="name" value="<?= e($c['name']) ?>" required>
                    <input type="text" name="description" value="<?= e($c['description']) ?>" required>
                    <button class="btn-secondary small">Sačuvaj</button>
                </form>
            </td>
            <td><?= (int)$c['listing_count'] ?></td>
            <td>
                <form method="POST" action="/admin/categories/delete" class="inline-form"
                      onsubmit="return confirm('Obrisati kategoriju? Oglasi ostaju bez kategorije.');">
                    <?= \App\Support\Csrf::field() ?>
                    <input type="hidden" name="category_id" value="<?= (int)$c['category_id'] ?>">
                    <button class="btn-danger small">Obriši</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($categories)): ?>
        <tr><td colspan="5" class="muted">Nema kategorija.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/partials/footer.php'; ?>

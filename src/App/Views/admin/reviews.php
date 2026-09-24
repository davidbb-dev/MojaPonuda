<?php require __DIR__ . '/partials/header.php'; ?>

<h1>Recenzije</h1>

<table class="admin-table">
    <thead><tr><th>ID</th><th>Ocena</th><th>Recenzent</th><th>Prodavac</th><th>Oglas</th><th>Komentar</th><th>Akcije</th></tr></thead>
    <tbody>
    <?php foreach ($reviews as $r): ?>
        <tr>
            <td><?= (int)$r['review_id'] ?></td>
            <td><?= str_repeat('★', (int)$r['rating']) ?></td>
            <td><?= e($r['reviewer']) ?></td>
            <td><?= e($r['seller']) ?></td>
            <td><?= e($r['listing_name']) ?></td>
            <td class="comment-cell"><?= e($r['comment'] ?? '') ?></td>
            <td>
                <form method="POST" action="/admin/reviews/delete" class="inline-form"
                      onsubmit="return confirm('Obrisati recenziju?');">
                    <?= \App\Support\Csrf::field() ?>
                    <input type="hidden" name="review_id" value="<?= (int)$r['review_id'] ?>">
                    <button class="btn-danger small">Obriši</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($reviews)): ?>
        <tr><td colspan="7" class="muted">Nema recenzija.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/partials/footer.php'; ?>

<?php require __DIR__ . '/partials/header.php'; ?>

<h1>Korisnici</h1>

<form class="admin-search" method="GET" action="/admin/users">
    <input type="text" name="q" placeholder="Pretraga (ime, email, username)..." value="<?= e($_GET['q'] ?? '') ?>">
    <button type="submit" class="btn-primary">Traži</button>
</form>

<table class="admin-table">
    <thead>
        <tr><th>ID</th><th>Korisnik</th><th>Email</th><th>Uloga</th><th>Status</th><th>Oglasa</th><th>Akcije</th></tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= (int)$u['user_id'] ?></td>
            <td>
                <a href="/user/<?= (int)$u['user_id'] ?>"><?= e($u['username']) ?></a><br>
                <span class="muted"><?= e($u['name'] . ' ' . $u['lastname']) ?></span>
            </td>
            <td><?= e($u['email']) ?></td>
            <td>
                <?php if ($admin['role'] === 'superadmin'): ?>
                    <form method="POST" action="/admin/users/role" class="inline-form">
                        <?= \App\Support\Csrf::field() ?>
                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                        <select name="role" onchange="this.form.submit()">
                            <?php foreach (['user', 'admin', 'superadmin'] as $r): ?>
                                <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= $r ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php else: ?>
                    <span class="badge"><?= e($u['role']) ?></span>
                <?php endif; ?>
            </td>
            <td>
                <span class="badge <?= $u['status'] === 'blocked' ? 'badge-red' : 'badge-green' ?>"><?= e($u['status']) ?></span>
            </td>
            <td><?= (int)$u['listing_count'] ?></td>
            <td class="actions">
                <form method="POST" action="/admin/users/status" class="inline-form">
                    <?= \App\Support\Csrf::field() ?>
                    <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                    <?php if ($u['status'] === 'blocked'): ?>
                        <input type="hidden" name="status" value="active">
                        <button class="btn-secondary small">Odblokiraj</button>
                    <?php else: ?>
                        <input type="hidden" name="status" value="blocked">
                        <button class="btn-warn small">Blokiraj</button>
                    <?php endif; ?>
                </form>
                <?php if ($admin['role'] === 'superadmin'): ?>
                    <form method="POST" action="/admin/users/delete" class="inline-form"
                          onsubmit="return confirm('Obrisati korisnika i sve njegove oglase?');">
                        <?= \App\Support\Csrf::field() ?>
                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                        <button class="btn-danger small">Obriši</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($users)): ?>
        <tr><td colspan="7" class="muted">Nema korisnika.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/partials/footer.php'; ?>

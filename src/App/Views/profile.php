<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moj profil — MojaPonuda</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/catalog.css">
</head>
<body>

<?php require __DIR__ . '/partials/navbar.php'; ?>

<div class="container narrow">
    <h1 class="section-title">Moj profil</h1>
    <?php require __DIR__ . '/partials/flash_message.php'; ?>

    <div class="profile-cards">

        <div class="form-card">
            <div class="avatar-block">
                <?php if (!empty($user['avatar_path'])): ?>
                    <img class="profile-avatar" src="<?= e($user['avatar_path']) ?>" alt="">
                <?php else: ?>
                    <span class="profile-avatar placeholder">👤</span>
                <?php endif; ?>
                <form method="POST" action="/profile/avatar" enctype="multipart/form-data">
                    <?= \App\Support\Csrf::field() ?>
                    <input type="file" name="avatar" accept="image/*" required>
                    <button type="submit" class="btn-secondary small">Promeni sliku</button>
                </form>
            </div>
            <p class="muted">Javni profil: <a href="/user/<?= (int)$user['user_id'] ?>">pogledaj</a></p>
        </div>

        <div class="form-card">
            <h2>Osnovni podaci</h2>
            <form method="POST" action="/profile">
                <?= \App\Support\Csrf::field() ?>
                <label>Ime
                    <input type="text" name="name" value="<?= e($user['name']) ?>" required>
                </label>
                <label>Prezime
                    <input type="text" name="lastname" value="<?= e($user['lastname']) ?>" required>
                </label>
                <label>Korisničko ime
                    <input type="text" name="username" value="<?= e($user['username']) ?>" required>
                </label>
                <label>Email
                    <input type="email" name="email" value="<?= e($user['email']) ?>" required>
                </label>
                <label>O meni
                    <textarea name="bio" maxlength="500" placeholder="Kratak opis..."><?= e($user['bio'] ?? '') ?></textarea>
                </label>
                <button type="submit" class="btn-primary">Sačuvaj izmene</button>
            </form>
        </div>

        <div class="form-card">
            <h2>Promena lozinke</h2>
            <form method="POST" action="/profile/password">
                <?= \App\Support\Csrf::field() ?>
                <label>Trenutna lozinka
                    <input type="password" name="current_password" required>
                </label>
                <label>Nova lozinka (min 6)
                    <input type="password" name="new_password" minlength="6" required>
                </label>
                <button type="submit" class="btn-primary">Promeni lozinku</button>
            </form>
        </div>

    </div>
</div>

</body>
</html>

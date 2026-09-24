<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kreiraj nalog</title>

    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/register.css">
</head>
<body>

    <?php require __DIR__ . '/partials/navbar.php'; ?>

    <div class="container register-container">

        <div class="register-card">

            <h1 class="register-title">Napravi novi nalog</h1>

            <form action="/register" method="POST" class="register-form">
                <?= \App\Support\Csrf::field() ?>

                <input
                    type="text"
                    name="name"
                    class="register-input"
                    placeholder="Unesite ime"
                    value="<?= e($old['name'] ?? '') ?>"
                    required
                >

                <input
                    type="text"
                    name="lastname"
                    class="register-input"
                    placeholder="Unesite prezime"
                    value="<?= e($old['lastname'] ?? '') ?>"
                    required
                >

                <input
                    type="text"
                    name="username"
                    class="register-input"
                    placeholder="Unesite korisnicko ime"
                    value="<?= e($old['username'] ?? '') ?>"
                    required
                >

                <input
                    type="email"
                    name="email"
                    class="register-input"
                    placeholder="Unesite email"
                    value="<?= e($old['email'] ?? '') ?>"
                    required
                >

                <input
                    type="password"
                    name="password"
                    class="register-input"
                    placeholder="Unesite lozinku"
                    required
                >

                <button
                    type="submit"
                    name="btnRegister"
                    class="register-button"
                >
                    Registrujte se
                </button>

            </form>

            <?php if ($flashMessage !== null): ?>
                <p class="register-flash">
                    <?= e($flashMessage) ?>
                </p>
            <?php endif; ?>

        </div>

    </div>

</body>
</html>
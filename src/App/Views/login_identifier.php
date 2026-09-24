<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prijava</title>

    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/login.css">
</head>
<body>

    <?php require __DIR__ . '/partials/navbar.php'; ?>

    <div class="container login-container">

        <div class="login-card">

            <h1 class="login-title">
                Prijavite se na vaš nalog
            </h1>

            <form
                action="/login/identifier"
                method="POST"
                class="login-form"
            >
                <?= \App\Support\Csrf::field() ?>

                <input
                    type="text"
                    name="identifier"
                    placeholder="Email ili korisničko ime"
                    class="login-input"
                    autocomplete="username"
                    required
                >

                <button
                    type="submit"
                    name="btnIdentityCheck"
                    class="login-button"
                >
                    Nastavite
                </button>

            </form>

            <?php
                use App\Http\Session;

                $session = new Session();
                $flashMessage = $session->getFlash('flash_message');

                if ($flashMessage !== null):
            ?>
            <p class="flash-message-login">
                <?= htmlspecialchars($flashMessage) ?>
            </p>

            <?php endif; ?>

        </div>

    </div>
</body>
</html>
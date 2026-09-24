<?php
    $navUserId = $_SESSION['user_id'] ?? null;
    $navUsername = $_SESSION['username'] ?? '';
    $navRole = $_SESSION['role'] ?? 'user';
    $navIsAdmin = in_array($navRole, ['admin', 'superadmin'], true);
?>
<?php if (!empty($navUserId)): ?>
    <meta name="csrf-token" content="<?= \App\Support\Csrf::token() ?>">
<?php endif; ?>
<nav class="navbar">
    <div class="nav-left">
        <a href="/" class="no-underline brand">MojaPonuda</a>
    </div>

    <form class="nav-search" action="/search" method="GET" role="search">
        <input type="text" name="q" placeholder="Pretraži oglase..." value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" aria-label="Pretraga">🔍</button>
    </form>

    <div class="nav-center">
        <div class="navbar-link">
            <a href="/categories" class="no-underline">Kategorije</a>
        </div>

        <?php if (!empty($navUserId)): ?>
            <div class="navbar-link">
                <a href="/my-listings" class="no-underline">Moji oglasi</a>
            </div>
            <div class="navbar-link">
                <a href="/create/listing" class="no-underline">Dodaj oglas</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="nav-right">
        <?php if (empty($navUserId)): ?>
            <div class="navbar-link"><a href="/login/identifier" class="no-underline">Prijava</a></div>
            <div class="navbar-link"><a href="/register" class="no-underline">Registracija</a></div>
        <?php else: ?>
            <?php if ($navIsAdmin): ?>
                <div class="navbar-link"><a href="/admin" class="no-underline admin-link">⚙️ Admin</a></div>
            <?php endif; ?>

            <div class="navbar-link"><a href="/favorites" class="no-underline" title="Omiljeno">❤️</a></div>
            <div class="navbar-link"><a href="/orders" class="no-underline" title="Kupovine i prodaje">🧾</a></div>

            <div class="navbar-link">
                <a href="/notifications" class="no-underline notif-link" title="Obaveštenja">
                    🔔<span id="notif-badge" class="notif-badge" hidden>0</span>
                </a>
            </div>

            <div class="navbar-link">
                <a href="/messages/inbox" class="inbox-btn no-underline">📩 Inbox</a>
            </div>

            <div class="navbar-link">
                <a href="/profile" class="no-underline user-name">👤 <?= htmlspecialchars($navUsername, ENT_QUOTES, 'UTF-8') ?></a>
            </div>

            <form method="POST" action="/logout" class="navbar-link">
                <?= \App\Support\Csrf::field() ?>
                <button type="submit" class="no-underline">Odjava</button>
            </form>
        <?php endif; ?>
    </div>
</nav>
<?php if (!empty($navUserId)): ?>
    <script src="/js/app.js" defer></script>
<?php endif; ?>
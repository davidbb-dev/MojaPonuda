<?php if(!empty($flashMessage)): ?>
    <div class="flash-message" role="status">
        <span class="flash-text"><?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?></span>
        <button type="button" class="flash-close" aria-label="Zatvori" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <script>
        (function () {
            var el = document.currentScript.previousElementSibling;
            if (!el) return;
            setTimeout(function () {
                el.style.transition = 'opacity .4s ease';
                el.style.opacity = '0';
                setTimeout(function () { el.remove(); }, 400);
            }, 5000);
        })();
    </script>
<?php endif; ?>

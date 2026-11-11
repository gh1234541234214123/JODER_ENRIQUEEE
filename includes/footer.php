</main>
<footer class="container text-center text-muted small py-4">
    <?= e(SITE_NAME) ?>
</footer>

<div id="cookie-consent" class="cookie-consent" hidden>
    <div class="container d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span class="small mb-0">
            Usamos cookies esenciales para mantener tu sesión y recordar tus preferencias.
        </span>
        <button type="button" class="btn btn-sm btn-primary" id="cookie-consent-accept">Entendido</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var banner = document.getElementById('cookie-consent');
    if (!banner) { return; }

    var accepted = document.cookie.split('; ').some(function (c) {
        return c.indexOf('cookie_consent=') === 0;
    });
    if (!accepted) { banner.hidden = false; }

    var accept = document.getElementById('cookie-consent-accept');
    if (accept) {
        accept.addEventListener('click', function () {
            document.cookie = 'cookie_consent=1; max-age=31536000; path=/; samesite=Lax';
            banner.hidden = true;
        });
    }
})();
</script>
</body>
</html>

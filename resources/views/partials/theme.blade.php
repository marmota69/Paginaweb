{{--
    One persisted theme for the whole site — public portfolio and dashboard.

    Flux owns the choice: `@fluxAppearance` resolves it from localStorage
    ("flux.appearance"), applies the `dark` class before first paint and keeps
    it in step with the OS. The portfolio's design system keys off `data-theme`
    instead, so all this does is mirror the class onto the attribute.

    Keeping the resolution in one place is deliberate — two scripts deciding
    the theme is what lost the setting on every reload.
--}}
<script>
    (function () {
        var root = document.documentElement;

        function sync() {
            var dark = root.classList.contains('dark');

            if (root.getAttribute('data-theme') !== (dark ? 'dark' : 'light')) {
                root.setAttribute('data-theme', dark ? 'dark' : 'light');
            }
        }

        sync();

        new MutationObserver(sync).observe(root, { attributes: true, attributeFilter: ['class'] });
    })();
</script>

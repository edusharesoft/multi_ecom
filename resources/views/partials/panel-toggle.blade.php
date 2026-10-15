{{-- Phase 11: ☰ button — desktop: icons-only sidebar (remembered); mobile: slide-in menu --}}
<script>
(() => {
    const body = document.body, sb = document.getElementById('sidebar'), btn = document.getElementById('sbToggle');
    try { if (localStorage.getItem('sb_mini') === '1' && innerWidth >= 992) body.classList.add('sb-mini'); } catch (e) {}
    btn && btn.addEventListener('click', () => {
        if (innerWidth >= 992) {
            body.classList.toggle('sb-mini');
            try { localStorage.setItem('sb_mini', body.classList.contains('sb-mini') ? '1' : '0'); } catch (e) {}
        } else {
            sb.classList.toggle('show');
        }
    });
})();
</script>

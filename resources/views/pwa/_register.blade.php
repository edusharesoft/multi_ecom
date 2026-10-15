{{-- Phase 10 PWA: service worker + install button + offline notice. Params: $sw, $scope, $cachePrefix --}}
<div id="pwaOffline" class="d-none position-fixed bottom-0 start-0 end-0 text-center small py-1" style="z-index:2000;background:#111827;color:#fff">
    <i class="fa-solid fa-wifi"></i> আপনি অফলাইনে আছেন — আগে সংরক্ষিত তথ্য দেখানো হচ্ছে
</div>
<button id="pwaInstall" type="button" class="btn btn-dark btn-sm shadow d-none position-fixed" style="z-index:1990;left:14px;bottom:14px;border-radius:20px">
    <i class="fa-solid fa-download"></i> অ্যাপ ইনস্টল করুন
</button>
<script>
(() => {
    const offline = document.getElementById('pwaOffline');
    const net = () => offline.classList.toggle('d-none', navigator.onLine);
    addEventListener('online', net); addEventListener('offline', net); net();

    if (!('serviceWorker' in navigator)) return;   // needs HTTPS (or localhost)
    addEventListener('load', () => navigator.serviceWorker.register(@json($sw), {scope: @json($scope)}).catch(e => console.warn('PWA:', e)));

    // "Install app" button (Android / desktop Chrome, Edge)
    let prompt = null;
    const btn = document.getElementById('pwaInstall');
    let dismissed = false;
    try { dismissed = localStorage.getItem('pwa_install_dismissed') === '1'; } catch (e) {}
    addEventListener('beforeinstallprompt', e => {
        e.preventDefault(); prompt = e;
        if (!dismissed && !matchMedia('(display-mode: standalone)').matches) btn.classList.remove('d-none');
    });
    btn.addEventListener('click', async () => {
        btn.classList.add('d-none');
        if (!prompt) return;
        prompt.prompt();
        const r = await prompt.userChoice.catch(() => null);
        if (r && r.outcome !== 'accepted') { try { localStorage.setItem('pwa_install_dismissed', '1'); } catch (e) {} }
        prompt = null;
    });
    addEventListener('appinstalled', () => btn.classList.add('d-none'));

    // logging out removes saved pages from this device (unsynced POS sales are kept)
    document.addEventListener('submit', e => {
        const form = e.target;
        const action = form.getAttribute('action') || '';
        if (!/\/logout(\?|$)/.test(action) || !window.caches || form.dataset.pwaCleared) return;
        e.preventDefault();
        const clear = caches.keys().then(keys => Promise.all(keys.filter(k => k.startsWith(@json($cachePrefix))).map(k => caches.delete(k))));
        Promise.race([clear, new Promise(r => setTimeout(r, 800))]).finally(() => { form.dataset.pwaCleared = '1'; form.submit(); });
    }, true);
})();
</script>

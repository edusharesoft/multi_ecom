{{-- Phase 10 PWA: service worker + install button + offline notice. Params: $sw, $scope, $cachePrefix --}}
<div id="pwaOffline" class="d-none position-fixed bottom-0 start-0 end-0 text-center small py-1" style="z-index:2000;background:#111827;color:#fff">
    <i class="fa-solid fa-wifi"></i> আপনি অফলাইনে আছেন — আগে সংরক্ষিত তথ্য দেখানো হচ্ছে
</div>
<script>
(() => {
    const offline = document.getElementById('pwaOffline');
    const net = () => offline.classList.toggle('d-none', navigator.onLine);
    addEventListener('online', net); addEventListener('offline', net); net();

    const buttons = [document.getElementById('pwaInstallTop'), document.getElementById('pwaInstall')].filter(Boolean);
    const setButtons = (show) => buttons.forEach(b => b.classList.toggle('d-none', !show));
    if (!('serviceWorker' in navigator)) return;   // needs HTTPS (or localhost)
    addEventListener('load', () => navigator.serviceWorker.register(@json($sw), {scope: @json($scope)}).catch(e => console.warn('PWA:', e)));

    // "Install app" button (Android / desktop Chrome, Edge)
    let prompt = null;
    let dismissed = false;
    try { dismissed = localStorage.getItem('pwa_install_dismissed') === '1'; } catch (e) {}
    const standalone = matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
    if (standalone) setButtons(false);
    else if (isIos && !dismissed) setButtons(true);
    addEventListener('beforeinstallprompt', e => {
        e.preventDefault(); prompt = e;
        if (!dismissed && !standalone) setButtons(true);
    });
    buttons.forEach(btn => btn.addEventListener('click', async () => {
        if (!prompt) {
            alert('iPhone: Share বাটন চাপুন → Add to Home Screen নির্বাচন করুন। Android-এ ব্রাউজারের মেনু থেকে Install app নির্বাচন করুন।');
            return;
        }
        setButtons(false);
        prompt.prompt();
        const r = await prompt.userChoice.catch(() => null);
        if (r && r.outcome !== 'accepted') { try { localStorage.setItem('pwa_install_dismissed', '1'); } catch (e) {} }
        prompt = null;
    }));
    addEventListener('appinstalled', () => setButtons(false));

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

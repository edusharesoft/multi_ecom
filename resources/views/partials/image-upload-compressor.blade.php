<script>
// Reduce phone-camera uploads before they leave the browser (server compression is the final safeguard).
(function () {
    const LIMIT = 900 * 1024, EDGE = 1600;
    const toBlob = (file) => new Promise((resolve) => {
        if (!file || !file.type.startsWith('image/') || file.type === 'image/svg+xml') return resolve(file);
        const reader = new FileReader();
        reader.onerror = () => resolve(file);
        reader.onload = () => {
            const img = new Image();
            img.onerror = () => resolve(file);
            img.onload = () => {
                const scale = Math.min(1, EDGE / Math.max(img.width, img.height));
                const canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(img.width * scale)); canvas.height = Math.max(1, Math.round(img.height * scale));
                const ctx = canvas.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, canvas.width, canvas.height); ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                let quality = 0.82;
                const next = () => canvas.toBlob((blob) => {
                    if (!blob || (blob.size <= LIMIT || quality <= 0.5)) return resolve(blob && blob.size < file.size ? new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', {type: 'image/jpeg', lastModified: Date.now()}) : file);
                    quality -= 0.08; next();
                }, 'image/jpeg', quality);
                next();
            };
            img.src = reader.result;
        };
        reader.readAsDataURL(file);
    });
    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (form.dataset.compressing === '1' || !form.querySelector('input[type=file]')) return;
        const inputs = [...form.querySelectorAll('input[type=file]')].filter(i => i.files && i.files.length);
        if (!inputs.length) return;
        event.preventDefault(); form.dataset.compressing = '1';
        for (const input of inputs) {
            const files = await Promise.all([...input.files].map(toBlob));
            const dt = new DataTransfer(); files.forEach(f => dt.items.add(f)); input.files = dt.files;
        }
        form.requestSubmit(event.submitter || undefined);
    }, true);
})();
</script>

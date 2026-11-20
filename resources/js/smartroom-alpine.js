// Komponen Alpine.js untuk SmartRoom. Didaftarkan dari resources/js/app.js.
export function registerSmartRoom(Alpine) {
    // Tombol "Salin" kunci ruangan. Memakai Clipboard API bila tersedia (hanya di HTTPS atau localhost),
    // dan cara lama sebagai cadangan agar tetap jalan di http://IP-laptop.
    Alpine.data('copyText', () => ({
        copied: false,

        copy(value) {
            const done = () => {
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 1800);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(value).then(done);
                return;
            }

            const area = document.createElement('textarea');
            area.value = value;
            area.setAttribute('readonly', '');
            area.style.position = 'fixed';
            area.style.opacity = '0';
            document.body.appendChild(area);
            area.select();

            try {
                if (document.execCommand('copy')) { done(); }
            } finally {
                area.remove();
            }
        },
    }));
}

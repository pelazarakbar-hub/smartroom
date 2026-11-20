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

    // Panel "Ubah nama" di Profil: inisial pada avatar ikut berubah saat mengetik,
    // tombol Simpan aktif hanya bila nama valid dan berbeda dari nama sekarang.
    Alpine.data('profileName', (original, value, editing) => ({
        original,
        name: value,
        editing,

        get initials() {
            // Sama dengan User::initials() di server: dua huruf pertama nama.
            return this.name.trim().slice(0, 2).toUpperCase() || '?';
        },

        get canSave() {
            const clean = this.name.trim();
            return clean.length >= 2 && clean !== this.original;
        },

        start(input) {
            this.editing = true;
            this.$nextTick(() => { input.focus(); input.select(); });
        },

        cancel() {
            if (! this.editing) { return; }
            this.editing = false;
            this.name = this.original;
        },
    }));
}

/* SmartRoom — interaksi halaman (JavaScript biasa, tanpa library). */
(function () {
  'use strict';

  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  // ------------------------------------------------------------ bantu
  function post(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify(body || {})
    }).then(function (response) {
      if (response.status === 401 || response.status === 419) {
        window.location.href = '/login';
        return Promise.reject(new Error('Sesi berakhir'));
      }
      return response.json().catch(function () { return {}; }).then(function (data) {
        if (!response.ok) {
          var error = new Error(data.message || 'Permintaan gagal. Coba lagi.');
          error.data = data;
          throw error;
        }
        return data;
      });
    });
  }

  var noticeTimer = null;

  function notice(message, kind, undo) {
    var host = document.getElementById('notice-host');
    if (!host) { return; }
    window.clearTimeout(noticeTimer);
    host.innerHTML = '';

    var box = document.createElement('div');
    box.className = 'action-notice ' + (kind || 'success');
    box.setAttribute('role', 'status');

    if (kind === 'loading') {
      var spin = document.createElement('span');
      spin.className = 'spinner';
      spin.setAttribute('aria-hidden', 'true');
      box.appendChild(spin);
    }

    var text = document.createElement('span');
    text.textContent = message;
    box.appendChild(text);

    if (undo) {
      var undoButton = document.createElement('button');
      undoButton.type = 'button';
      undoButton.textContent = 'Urungkan';
      undoButton.addEventListener('click', function () { host.innerHTML = ''; undo(); });
      box.appendChild(undoButton);
    }

    if (kind !== 'loading') {
      var close = document.createElement('button');
      close.type = 'button';
      close.className = 'notice-close';
      close.setAttribute('aria-label', 'Tutup pemberitahuan');
      close.textContent = '×';
      close.addEventListener('click', function () { host.innerHTML = ''; });
      box.appendChild(close);
      noticeTimer = window.setTimeout(function () { host.innerHTML = ''; }, 4500);
    }

    host.appendChild(box);
  }

  function each(selector, fn) {
    Array.prototype.forEach.call(document.querySelectorAll(selector), fn);
  }

  // ------------------------------------------------------------ form masuk/daftar: cegah kirim ganda
  each('form[data-submit-busy]', function (form) {
    form.addEventListener('submit', function () {
      var button = form.querySelector('button[type="submit"]');
      if (!button || button.disabled) { return; }
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
      button.insertAdjacentHTML('afterbegin', '<span class="spinner" aria-hidden="true"></span>');
      // Jika server menolak dan halaman kembali lewat tombol "kembali", tombol dipulihkan.
      window.addEventListener('pageshow', function () {
        button.disabled = false;
        button.removeAttribute('aria-busy');
        var spin = button.querySelector('.spinner');
        if (spin) { spin.remove(); }
      });
    });
  });

  // ------------------------------------------------------------ konfirmasi hapus (admin)
  each('form[data-confirm]', function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        event.preventDefault();
      }
    });
  });

  // ------------------------------------------------------------ perangkat (halaman Perangkat)
  var devicePage = document.querySelector('[data-devices]');

  function setToggle(button, on) {
    button.classList.toggle('is-on', on);
    button.setAttribute('aria-checked', on ? 'true' : 'false');
    var name = button.getAttribute('data-name') || 'Perangkat';
    button.setAttribute('aria-label', name + ': ' + (on ? 'aktif' : 'nonaktif'));
    var label = button.parentNode.querySelector('small');
    if (label) { label.textContent = on ? 'ON' : 'OFF'; }
  }

  function updateActiveCount() {
    var counter = document.querySelector('[data-active-count]');
    if (!counter) { return; }
    counter.textContent = String(document.querySelectorAll('[data-device-toggle].is-on').length);
  }

  function updateFanSlider() {
    var fan = document.querySelector('[data-device-toggle][data-type="fan"]');
    var slider = document.querySelector('[data-speed-input]');
    var guidance = document.querySelector('[data-speed-guidance]');
    if (!fan || !slider) { return; }
    var on = fan.classList.contains('is-on');
    slider.disabled = !on;
    if (guidance) { guidance.hidden = on; }
  }

  function setBusy(busy) {
    each('[data-device-toggle], [data-speed-input]', function (control) {
      if (busy) {
        control.setAttribute('data-was-disabled', control.disabled ? '1' : '0');
        control.disabled = true;
      } else {
        control.disabled = control.getAttribute('data-was-disabled') === '1';
        control.removeAttribute('data-was-disabled');
      }
    });
    if (!busy) { updateFanSlider(); }
  }

  function toggleDevice(button, next, afterDone) {
    var name = button.getAttribute('data-name') || 'Perangkat';
    var previous = !next;
    setBusy(true);
    notice((next ? 'Menyalakan ' : 'Mematikan ') + name + '...', 'loading');

    post(button.getAttribute('data-url'), { on: next }).then(function (data) {
      setToggle(button, data.is_on);
      setBusy(false);
      updateActiveCount();
      if (afterDone) { afterDone(data); return; }
      notice(name + (data.is_on ? ' berhasil dinyalakan.' : ' berhasil dimatikan.'), 'success', function () {
        toggleDevice(button, previous);
      });
    }).catch(function (error) {
      setBusy(false);
      notice(error.message, 'error');
    });
  }

  if (devicePage) {
    each('[data-device-toggle]', function (button) {
      button.addEventListener('click', function () {
        toggleDevice(button, !button.classList.contains('is-on'));
      });
    });

    var slider = document.querySelector('[data-speed-input]');
    if (slider) {
      var currentLevel = slider.value;
      var levelLabel = document.querySelector('[data-speed-label]');

      slider.addEventListener('input', function () {
        if (levelLabel) { levelLabel.textContent = 'Level ' + slider.value; }
      });

      slider.addEventListener('change', function () {
        var requested = slider.value;
        setBusy(true);
        notice('Mengatur kecepatan kipas ke level ' + requested + '...', 'loading');

        post(slider.getAttribute('data-url'), { level: Number(requested) }).then(function (data) {
          currentLevel = String(data.level);
          setBusy(false);
          notice('Kecepatan kipas berhasil diatur ke level ' + data.level + '.', 'success');
        }).catch(function (error) {
          slider.value = currentLevel;
          if (levelLabel) { levelLabel.textContent = 'Level ' + currentLevel; }
          setBusy(false);
          notice(error.message, 'error');
        });
      });
    }
  }

  // ------------------------------------------------------------ tombol kipas "One-Tap" di Beranda
  each('[data-fan-quick]', function (button) {
    button.addEventListener('click', function () {
      var next = button.getAttribute('data-next') === '1';
      var original = button.innerHTML;
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
      button.innerHTML = '<span class="spinner" aria-hidden="true"></span>Mengirim perintah...';

      post(button.getAttribute('data-url'), { on: next }).then(function () {
        window.location.reload();
      }).catch(function (error) {
        button.disabled = false;
        button.removeAttribute('aria-busy');
        button.innerHTML = original;
        notice(error.message, 'error');
      });
    });
  });

  // ------------------------------------------------------------ AI Insight
  each('[data-apply-insight]', function (button) {
    button.addEventListener('click', function () {
      var original = button.innerHTML;
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
      button.innerHTML = '<span class="spinner dark" aria-hidden="true"></span>Menerapkan saran...';

      post(button.getAttribute('data-url')).then(function (data) {
        button.removeAttribute('aria-busy');
        button.textContent = 'Saran diterapkan';
        var target = document.querySelector('[data-applied-text]');
        if (target) { target.textContent = data.message; target.hidden = false; }
        notice('Aturan otomatis berhasil dibuat dan sudah aktif.', 'success');
      }).catch(function (error) {
        button.disabled = false;
        button.removeAttribute('aria-busy');
        button.innerHTML = original;
        notice(error.message, 'error');
      });
    });
  });

  each('[data-feedback]', function (group) {
    var buttons = group.querySelectorAll('button');
    Array.prototype.forEach.call(buttons, function (button) {
      button.addEventListener('click', function () {
        var helpful = button.getAttribute('data-helpful') === '1';
        post(group.getAttribute('data-url'), { helpful: helpful }).then(function () {
          Array.prototype.forEach.call(buttons, function (other) {
            other.classList.toggle('selected', other === button);
          });
        }).catch(function (error) {
          notice(error.message, 'error');
        });
      });
    });
  });

  // ------------------------------------------------------------ Profil
  each('[data-setting]', function (button) {
    button.addEventListener('click', function () {
      var key = button.getAttribute('data-setting');
      var caption = document.querySelector('[data-setting-caption="' + key + '"]');
      var normal = caption ? caption.textContent : '';

      each('[data-setting]', function (other) { other.disabled = true; });
      if (caption) { caption.textContent = 'Menyimpan perubahan...'; }

      post(button.getAttribute('data-url'), { key: key }).then(function (data) {
        button.classList.toggle('is-on', data.value);
        button.setAttribute('aria-checked', data.value ? 'true' : 'false');
        if (key === 'privacy_mode') {
          var status = document.querySelector('[data-privacy-status]');
          if (status) { status.textContent = 'Status: ' + (data.value ? 'Mode privasi aktif' : 'Penyimpanan standar'); }
        }
      }).catch(function (error) {
        notice(error.message, 'error');
      }).then(function () {
        if (caption) { caption.textContent = normal; }
        each('[data-setting]', function (other) { other.disabled = false; });
      });
    });
  });

  // Ubah profil, panduan, dan dialog keluar memakai Alpine.js (lihat resources/js/app.js dan view profil).

  // ------------------------------------------------------------ pilih ruangan
  each('[data-auto-submit]', function (select) {
    select.addEventListener('change', function () { select.form.submit(); });
  });

  // ------------------------------------------------------------ segarkan data otomatis
  // Halaman data sensor dimuat ulang tiap 30 detik bila tab sedang terbuka dan tidak ada yang sedang diubah.
  var live = document.querySelector('[data-live-refresh]');
  if (live) {
    window.setInterval(function () {
      var busy = document.querySelector('.action-notice.loading') || document.querySelector('[aria-busy="true"]');
      if (document.visibilityState === 'visible' && !busy) { window.location.reload(); }
    }, 30000);
  }
})();

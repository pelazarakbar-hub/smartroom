(function () {
  // Menu mobile
  var toggle = document.querySelector('[data-menu-toggle]');
  var menu = document.querySelector('[data-menu]');
  if (toggle && menu) {
    var setOpen = function (open) {
      menu.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    toggle.addEventListener('click', function () {
      setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });
    menu.addEventListener('click', function (e) {
      if (e.target.closest('a')) setOpen(false);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') setOpen(false);
    });
  }

  // Demo ruangan (simulasi, tidak mengirim data apa pun)
  var demo = document.querySelector('[data-demo]');
  if (!demo) return;
  var $ = function (s) { return demo.querySelector(s); };
  var fan = $('[data-fan]'), lamp = $('[data-lamp]'), heat = $('[data-heat]');
  var tempEl = $('[data-temp]'), lightEl = $('[data-light]'), scoreEl = $('[data-score]');
  var ring = $('[data-ring]'), box = $('[data-insight]'), text = $('[data-insight-text]');
  var state = { fan: false, lamp: false };
  var C = 2 * Math.PI * 26;

  function render() {
    var temp = state.fan ? 27.6 : 31.0;
    var light = state.lamp ? 420 : 180;
    var score = state.fan ? 88 : 67;
    if (state.fan && state.lamp) score = 90;

    tempEl.textContent = temp.toFixed(1);
    lightEl.textContent = light;
    scoreEl.textContent = score;
    ring.style.strokeDasharray = (C * score / 100).toFixed(1) + ' ' + C.toFixed(1);
    ring.classList.toggle('ok', score >= 80);
    heat.style.opacity = state.fan ? 0.04 : 0.22;
    fan.classList.toggle('on', state.fan);
    lamp.classList.toggle('on', state.lamp);

    if (state.fan) {
      box.className = 'insight good';
      text.textContent = 'Suhu turun ke 27.6°C, sudah nyaman. Kipas bisa dimatikan kalau kamu keluar ruangan.';
    } else {
      box.className = 'insight warn';
      text.textContent = 'Suhu 31.0°C, di atas batas nyaman 30°C. Nyalakan kipas.';
    }
  }

  demo.querySelectorAll('[data-switch]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var k = btn.getAttribute('data-switch');
      state[k] = !state[k];
      btn.setAttribute('aria-checked', state[k] ? 'true' : 'false');
      render();
    });
  });
  render();
})();

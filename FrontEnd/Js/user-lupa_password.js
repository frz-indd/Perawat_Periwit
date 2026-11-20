(function () {
  var PESAN_EMAIL = 'Email tidak valid/tidak terdaftar';
  var PESAN_LIMIT = 'Terlalu banyak permintaan, coba beberapa saat lagi';
  var POLA_EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

  var $ = function (id) { return document.getElementById(id); };
  var form = $('form-lupa'), input = $('email'), err = $('error-email'), btn = $('btn-kirim');
  var ok = $('modal-sukses'), fail = $('modal-gagal');
  var aksiLogin = $('aksi-login'), aksiCoba = $('aksi-coba');
  var label = btn.textContent;
  var timer, siap = false, serverMsg = '', busy = false;

  function post(url, body) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    })
      .then(function (r) {
        return r.json().catch(function () { return {}; }).then(function (d) {
          return { ok: r.ok, status: r.status, data: d || {} };
        });
      })
      .catch(function () { return null; });
  }

  function formatSalah() {
    var v = input.value.trim();
    return !v || v.length > 254 || !POLA_EMAIL.test(v);
  }

  function pesan(paksa) {
    if (!input.value.trim()) return paksa ? PESAN_EMAIL : '';
    return formatSalah() ? PESAN_EMAIL : serverMsg;
  }

  function render(paksa) {
    var m = paksa || siap ? pesan(paksa) : '';
    err.textContent = m;
    err.hidden = !m;
    if (m) input.setAttribute('aria-invalid', 'true');
    else input.removeAttribute('aria-invalid');
  }

  function buka(modal, fokus) {
    form.setAttribute('inert', '');
    modal.hidden = false;
    fokus.focus();
  }

  function tutupGagal() {
    fail.hidden = true;
    form.removeAttribute('inert');
    input.focus();
  }

  input.addEventListener('input', function () {
    clearTimeout(timer);
    siap = false;
    serverMsg = '';
    render(false);
    if (input.value.trim()) timer = setTimeout(function () { siap = true; render(false); }, 600);
  });

  input.addEventListener('blur', function () {
    clearTimeout(timer);
    if (input.value.trim()) { siap = true; render(false); }
  });

  aksiCoba.addEventListener('click', tutupGagal);

  document.addEventListener('keydown', function (e) {
    var aktif = !fail.hidden ? fail : !ok.hidden ? ok : null;
    if (!aktif) return;
    if (e.key === 'Escape' && aktif === fail) { tutupGagal(); return; }
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (busy) return;
    clearTimeout(timer);
    siap = true;
    serverMsg = '';

    if (pesan(true)) {
      render(true);
      buka(fail, aksiCoba);
      return;
    }

    busy = true;
    btn.disabled = true;
    btn.textContent = 'Mengirim...';

    post(form.getAttribute('data-api-url'), { email: input.value.trim() }).then(function (r) {
      busy = false;
      btn.disabled = false;
      btn.textContent = label;

      if (r && r.ok) {
        render(false);
        try {
          if (r.data && r.data.token) {
            sessionStorage.setItem('user_reset_token', r.data.token);
          }
        } catch (e) {}
        buka(ok, aksiLogin);
        return;
      }

      if (r && (r.status === 404 || r.status === 403 || r.status === 422)) {
        serverMsg = PESAN_EMAIL;
      } else if (r && r.status === 429) {
        serverMsg = PESAN_LIMIT;
      } else {
        serverMsg = PESAN_EMAIL;
      }

      render(true);
      buka(fail, aksiCoba);
    });
  });

  window.addEventListener('pageshow', function (e) {
    if (e.persisted) location.reload();
  });
})();

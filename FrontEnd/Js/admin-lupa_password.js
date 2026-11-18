(function () {
  var $ = function (id) { return document.getElementById(id); };
  var form = $('form-lupa'), input = $('email'), err = $('error-email'), btn = $('btn-kirim');
  var ok = $('modal-sukses'), fail = $('modal-gagal'), label = btn.textContent;
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

  function cek(paksa) {
    var v = input.value.trim();
    if (!v) return paksa ? 'Email wajib diisi' : '';
    return v.length > 254 || !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v) ? 'Format email tidak valid' : serverMsg;
  }

  function render(paksa) {
    var m = paksa || siap ? cek(paksa) : '';
    err.textContent = m;
    err.hidden = !m;
    if (m) input.setAttribute('aria-invalid', 'true');
    else input.removeAttribute('aria-invalid');
  }

  function tutupGagal() {
    fail.hidden = true;
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

  $('aksi-coba').addEventListener('click', tutupGagal);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !fail.hidden) tutupGagal();
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (busy) return;
    clearTimeout(timer);
    siap = true;

    if (cek(true)) {
      render(true);
      fail.hidden = false;
      $('aksi-coba').focus();
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
        ok.hidden = false;
        $('aksi-login').focus();
        return;
      }
      if (r && [404, 422, 429].indexOf(r.status) > -1) {
        serverMsg = (r.data.errors && r.data.errors.email) || r.data.message || '';
      }
      render(true);
      fail.hidden = false;
      $('aksi-coba').focus();
    });
  });

  window.addEventListener('pageshow', function (e) {
    if (e.persisted) location.reload();
  });
})();
(function () {
  var $ = function (id) { return document.getElementById(id); };
  var KEY = 'admin_reset_token';
  var form = $('form-password'), stInvalid = $('state-tidak-valid'), btn = $('btn-simpan');
  var ok = $('modal-sukses'), fail = $('modal-gagal');
  var pw = $('password_baru'), cf = $('konfirmasi_password');
  var field = { baru: [pw, $('error-baru')], konf: [cf, $('error-konfirmasi')] };
  var siap = { baru: false, konf: false };
  var timer, srv = '', busy = false, token = '';

  function simpanToken(v) {
    try { if (v) sessionStorage.setItem(KEY, v); else sessionStorage.removeItem(KEY); } catch (e) {}
  }

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

  function ditolak(r) {
    return !!r && (r.data.status === 'invalid_token' || [401, 403, 404, 410].indexOf(r.status) > -1);
  }

  function tidakValid(pesan) {
    if (!pesan) { token = ''; simpanToken(''); }
    form.hidden = true;
    fail.hidden = true;
    if (pesan) $('info-tidak-valid').textContent = pesan;
    stInvalid.hidden = false;
  }

  function bytes(s) {
    return new TextEncoder().encode(s).length;
  }

  function kurang(p) {
    var k = [];
    if (Array.from(p).length < 8) k.push('Minimal 8 karakter');
    if (!/\p{Lu}/u.test(p)) k.push('1 huruf besar');
    if (!/\p{Ll}/u.test(p)) k.push('1 huruf kecil');
    if (!/[0-9]/.test(p)) k.push('1 angka');
    if (!/[^\p{L}\p{N}\s]/u.test(p)) k.push('1 simbol');
    return k;
  }

  function galat(paksa) {
    var g = {}, b = pw.value, k = cf.value, x;
    if (!b) { if (paksa) g.baru = 'Password baru wajib diisi'; }
    else if (bytes(b) > 72) g.baru = 'Password baru maksimal 72 byte';
    else if ((x = kurang(b)).length) g.baru = x.join(', ');
    else if (srv) g.baru = srv;

    if (!k) { if (paksa) g.konf = 'Konfirmasi password wajib diisi'; }
    else if (k !== b) g.konf = 'Konfirmasi password tidak sesuai';
    return g;
  }

  function render(paksa) {
    var g = galat(paksa);
    Object.keys(field).forEach(function (n) {
      var m = paksa || siap[n] ? g[n] || '' : '';
      field[n][1].textContent = m;
      field[n][1].hidden = !m;
      if (m) field[n][0].setAttribute('aria-invalid', 'true');
      else field[n][0].removeAttribute('aria-invalid');
    });
  }

  function tandai() {
    siap.baru = !!pw.value;
    siap.konf = !!cf.value;
    render(false);
  }

  [pw, cf].forEach(function (el) {
    el.addEventListener('input', function () {
      clearTimeout(timer);
      if (el === pw) srv = '';
      siap.baru = siap.baru && el !== pw;
      siap.konf = false;
      render(false);
      timer = setTimeout(tandai, 600);
    });
    el.addEventListener('blur', function () {
      clearTimeout(timer);
      if (el.value) tandai();
    });
  });

  Array.prototype.forEach.call(document.querySelectorAll('.ub-mata'), function (b) {
    b.addEventListener('click', function () {
      var el = $(b.getAttribute('data-target')), lihat = el.type === 'password';
      el.type = lihat ? 'text' : 'password';
      b.setAttribute('aria-pressed', lihat);
      b.setAttribute('aria-label', lihat ? 'Sembunyikan password' : 'Tampilkan password');
      el.focus();
    });
  });

  function tutupGagal() {
    fail.hidden = true;
    (field.konf[1].hidden ? pw : cf).focus();
  }

  $('aksi-coba').addEventListener('click', tutupGagal);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !fail.hidden) tutupGagal();
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (busy) return;
    clearTimeout(timer);
    siap.baru = siap.konf = true;

    if (Object.keys(galat(true)).length) {
      render(true);
      fail.hidden = false;
      $('aksi-coba').focus();
      return;
    }

    busy = true;
    btn.disabled = true;

    post(form.getAttribute('data-api-url'), {
      token: token,
      new_password: pw.value,
      confirm_password: cf.value
    }).then(function (r) {
      busy = false;
      btn.disabled = false;

      if (r && r.ok) {
        simpanToken('');
        ok.hidden = false;
        $('aksi-ok').focus();
        return;
      }
      if (ditolak(r)) { tidakValid(); return; }
      if (r && r.status === 422) {
        srv = (r.data.errors && r.data.errors.new_password) || r.data.message || '';
      }
      render(true);
      fail.hidden = false;
      $('aksi-coba').focus();
    });
  });

  window.addEventListener('pageshow', function (e) {
    if (e.persisted) location.reload();
  });

  var t = new URLSearchParams(location.search).get('token');
  if (t !== null) history.replaceState(null, '', location.pathname + location.hash);
  else try { t = sessionStorage.getItem(KEY); } catch (e) {}
  token = /^[a-f0-9]{64}$/.test(t || '') ? t : '';
  simpanToken(token);

  if (!token) { tidakValid(); return; }

  post(document.body.getAttribute('data-api-verify'), { token: token }).then(function (r) {
    if (r && r.ok) {
      form.hidden = false;
      pw.focus();
    } else if (ditolak(r)) {
      tidakValid();
    } else {
      tidakValid('Tautan tidak dapat diverifikasi saat ini. Periksa koneksi internet lalu muat ulang halaman, atau minta tautan baru.');
    }
  });
})();
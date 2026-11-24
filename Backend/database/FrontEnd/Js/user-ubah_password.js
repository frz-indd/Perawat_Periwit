(function () {
  var KEY = 'user_reset_token';
  var MIN_KARAKTER = 8;
  var MAKS_BYTE = 72;
  var PESAN_BARU = 'Password baru wajib diisi dan memenuhi syarat';
  var PESAN_MAKS = 'Password baru maksimal 72 byte';
  var PESAN_KONF = 'Konfirmasi password tidak cocok';
  var JUDUL_KEDALUWARSA = 'Tautan Reset Kadaluarsa';
  var INFO_KEDALUWARSA = 'Maaf, tautan reset password sudah tidak berlaku atau telah melewati batas waktu penggunaan.';
  var JUDUL_GAGAL_VERIFIKASI = 'Tautan Belum Dapat Diverifikasi';
  var INFO_GAGAL_VERIFIKASI = 'Periksa koneksi internet lalu muat ulang halaman, atau minta tautan reset baru.';

  var $ = function (id) { return document.getElementById(id); };
  var form = $('form-password'), stInvalid = $('state-tidak-valid'), btn = $('btn-simpan');
  var ok = $('modal-sukses'), fail = $('modal-gagal');
  var aksiOk = $('aksi-ok'), aksiCoba = $('aksi-coba');
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

  function tidakValid(judul, pesan) {
    if (!pesan) { token = ''; simpanToken(''); }
    form.hidden = true;
    fail.hidden = true;
    ok.hidden = true;
    $('judul-tidak-valid').textContent = judul || JUDUL_KEDALUWARSA;
    $('info-tidak-valid').textContent = pesan || INFO_KEDALUWARSA;
    stInvalid.hidden = false;
    $('aksi-minta-baru').focus();
  }

  function bytes(s) {
    return new TextEncoder().encode(s).length;
  }

  function galat(paksa) {
    var g = {}, b = pw.value, k = cf.value;

    if (!b) { if (paksa) g.baru = PESAN_BARU; }
    else if (Array.from(b).length < MIN_KARAKTER) g.baru = PESAN_BARU;
    else if (bytes(b) > MAKS_BYTE) g.baru = PESAN_MAKS;
    else if (srv) g.baru = srv;

    if (k !== b && (k || paksa)) g.konf = PESAN_KONF;
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

  function buka(modal, fokus) {
    form.setAttribute('inert', '');
    modal.hidden = false;
    fokus.focus();
  }

  function tutupGagal() {
    fail.hidden = true;
    form.removeAttribute('inert');
    if (!field.baru[1].hidden) pw.focus();
    else if (!field.konf[1].hidden) cf.focus();
    else pw.focus();
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

  aksiCoba.addEventListener('click', tutupGagal);

  document.addEventListener('keydown', function (e) {
    var aktif = !fail.hidden ? fail : !ok.hidden ? ok : null;
    if (!aktif) return;
    if (e.key === 'Escape' && aktif === fail) { tutupGagal(); return; }
    if (e.key === 'Tab') {
      e.preventDefault();
      (aktif === fail ? aksiCoba : aksiOk).focus();
    }
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (busy) return;
    clearTimeout(timer);
    srv = '';
    siap.baru = siap.konf = true;

    if (Object.keys(galat(true)).length) {
      render(true);
      buka(fail, aksiCoba);
      return;
    }

    busy = true;
    btn.disabled = true;

    post(form.getAttribute('data-api-url'), {
      email: email,
      new_password: pw.value,
      confirm_password: cf.value
    }).then(function (r) {
      busy = false;
      btn.disabled = false;

      if (r && r.ok) {
        try { sessionStorage.removeItem('user_reset_email'); } catch (e) {}
        buka(ok, aksiOk);
        return;
      }
      if (r && r.status === 422) {
        srv = (r.data.errors && r.data.errors.new_password) || r.data.message || '';
      }
      render(true);
      buka(fail, aksiCoba);
    });
  });

  window.addEventListener('pageshow', function (e) {
    if (e.persisted) location.reload();
  });

  var email = new URLSearchParams(location.search).get('email') || '';
  if (!email) {
    try { email = sessionStorage.getItem('user_reset_email') || ''; } catch (e) {}
  }
  form.hidden = false;
  pw.focus();
})();


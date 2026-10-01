/* =====================================================
   PASSWORD SHOW / HIDE
===================================================== */

const passwordToggleButtons = document.querySelectorAll('.password-toggle');

passwordToggleButtons.forEach(button => {

    button.addEventListener('click', function () {

        const targetId = this.getAttribute('data-target');
        const passwordInput = document.getElementById(targetId);
        const icon = this.querySelector('i');

        if (passwordInput.type === 'password') {

            passwordInput.type = 'text';

            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');

            this.setAttribute(
                'aria-label',
                'Sembunyikan password'
            );

        } else {

            passwordInput.type = 'password';

            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');

            this.setAttribute(
                'aria-label',
                'Tampilkan password'
            );
        }

    });

});


/* =====================================================
   LOGIN
===================================================== */

const loginForm = document.getElementById('loginForm');

if (loginForm) {

    loginForm.addEventListener('submit', function (event) {

        event.preventDefault();

        const identity = document.getElementById('loginIdentity');
        const password = document.getElementById('loginPassword');

        let isValid = true;


        // Validasi Email / Nomor Telephone
        if (identity.value.trim() === '') {

            identity.classList.add('is-invalid');
            identity.classList.remove('is-valid');

            isValid = false;

        } else {

            identity.classList.remove('is-invalid');
            identity.classList.add('is-valid');

        }


        // Validasi Password
        if (password.value.trim() === '') {

            password.classList.add('is-invalid');
            password.classList.remove('is-valid');

            isValid = false;

        } else {

            password.classList.remove('is-invalid');
            password.classList.add('is-valid');

        }


        // Jika valid
        if (isValid) {

            /*
                Untuk sekarang hanya frontend.

                Nanti bagian ini diganti dengan
                request API ke backend.

                Contoh:

                fetch('/api/login', {
                    method: 'POST',
                    body: JSON.stringify({
                        identity: identity.value,
                        password: password.value
                    })
                });
            */

            alert('Form login berhasil diisi.');

            console.log('Login:', {
                identity: identity.value,
                password: password.value
            });
        }

    });

}


/* =====================================================
   REGISTER
===================================================== */

const registerForm = document.getElementById('registerForm');

if (registerForm) {

    registerForm.addEventListener('submit', function (event) {

        event.preventDefault();

        const identity = document.getElementById('registerIdentity');
        const password = document.getElementById('registerPassword');
        const confirmPassword = document.getElementById('confirmPassword');

        const confirmPasswordError =
            document.getElementById('confirmPasswordError');

        let isValid = true;


        /* ---------------------------------------------
           Validasi Email / Nomor Telephone
        --------------------------------------------- */

        if (identity.value.trim() === '') {

            identity.classList.add('is-invalid');
            identity.classList.remove('is-valid');

            isValid = false;

        } else {

            identity.classList.remove('is-invalid');
            identity.classList.add('is-valid');

        }


        /* ---------------------------------------------
           Validasi Password
        --------------------------------------------- */

        if (password.value.trim() === '') {

            password.classList.add('is-invalid');
            password.classList.remove('is-valid');

            isValid = false;

        } else if (password.value.length < 6) {

            password.classList.add('is-invalid');
            password.classList.remove('is-valid');

            password.nextElementSibling.textContent =
                'Password minimal 6 karakter.';

            isValid = false;

        } else {

            password.classList.remove('is-invalid');
            password.classList.add('is-valid');

        }


        /* ---------------------------------------------
           Validasi Konfirmasi Password
        --------------------------------------------- */

        if (confirmPassword.value.trim() === '') {

            confirmPassword.classList.add('is-invalid');
            confirmPassword.classList.remove('is-valid');

            confirmPasswordError.textContent =
                'Verifikasi password wajib diisi.';

            isValid = false;

        } else if (password.value !== confirmPassword.value) {

            confirmPassword.classList.add('is-invalid');
            confirmPassword.classList.remove('is-valid');

            confirmPasswordError.textContent =
                'Password tidak sama.';

            isValid = false;

        } else {

            confirmPassword.classList.remove('is-invalid');
            confirmPassword.classList.add('is-valid');

        }


        /* ---------------------------------------------
           Jika semua valid
        --------------------------------------------- */

        if (isValid) {

            /*
                Untuk sekarang hanya frontend.

                Nanti dihubungkan ke API backend.

                Contoh:

                fetch('/api/register', {
                    method: 'POST',
                    body: JSON.stringify({
                        identity: identity.value,
                        password: password.value
                    })
                });
            */

            alert('Pendaftaran berhasil.');

            console.log('Register:', {
                identity: identity.value,
                password: password.value
            });

            /*
                Setelah backend sudah tersedia,
                bisa diarahkan ke login:

                window.location.href = 'login.html';
            */
        }

    });

}


/* =====================================================
   GOOGLE LOGIN / REGISTER
===================================================== */

const googleButtons = [
    document.getElementById('googleLogin'),
    document.getElementById('googleRegister')
];

googleButtons.forEach(button => {

    if (button) {

        button.addEventListener('click', function () {

            /*
                Nanti dihubungkan dengan
                Google OAuth / backend.
            */

            alert('Login dengan Google belum dihubungkan.');

        });

    }

});


/* =====================================================
   APPLE LOGIN / REGISTER
===================================================== */

const appleButtons = [
    document.getElementById('appleLogin'),
    document.getElementById('appleRegister')
];

appleButtons.forEach(button => {

    if (button) {

        button.addEventListener('click', function () {

            /*
                Nanti dihubungkan dengan
                Apple OAuth / backend.
            */

            alert('Login dengan Apple belum dihubungkan.');

        });

    }

});


/* =====================================================
   LUPA PASSWORD
===================================================== */

const forgotPassword = document.querySelector('.forgot-password');

if (forgotPassword) {

    forgotPassword.addEventListener('click', function (event) {

        event.preventDefault();

        alert('Halaman lupa password belum dibuat.');

    });

}


/* =====================================================
   INPUT VALIDATION SAAT DIISI
===================================================== */

const allInputs = document.querySelectorAll('.auth-input');

allInputs.forEach(input => {

    input.addEventListener('input', function () {

        if (this.value.trim() !== '') {

            this.classList.remove('is-invalid');

        }

    });

});
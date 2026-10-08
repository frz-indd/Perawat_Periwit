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

    const googleResult = new URLSearchParams(window.location.search).get('google');
    const googleMessages = {
        success: 'Login Google berhasil.',
        not_configured: 'Login Google belum dikonfigurasi di server.',
        cancelled: 'Login Google dibatalkan.'
    };
    const googleMessage = googleMessages[googleResult];

    if (googleMessage) {
        const message = document.getElementById('loginMessage');
        message.textContent = googleMessage;
        message.className = `alert mt-3 ${googleResult === 'success' ? 'alert-success' : 'alert-danger'}`;
    }

    loginForm.addEventListener('submit', async function (event) {

        event.preventDefault();

        const email = document.getElementById('loginIdentity');
        const password = document.getElementById('loginPassword');
        const message = document.getElementById('loginMessage');
        const submitButton = loginForm.querySelector('[type="submit"]');

        let isValid = true;

        if (!email.validity.valid || email.value.trim() === '') {
            email.classList.add('is-invalid');
            email.classList.remove('is-valid');
            isValid = false;
        } else {
            email.classList.remove('is-invalid');
            email.classList.add('is-valid');
        }

        if (password.value.trim() === '') {
            password.classList.add('is-invalid');
            password.classList.remove('is-valid');
            isValid = false;
        } else {
            password.classList.remove('is-invalid');
            password.classList.add('is-valid');
        }

        if (!isValid) return;

        message.classList.add('d-none');
        document.getElementById('loginPasswordError').classList.add('d-none');
        password.classList.remove('is-invalid');
        submitButton.disabled = true;

        try {
            const response = await fetch(loginForm.action, {
                method: 'POST',
                body: new FormData(loginForm),
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            });
            const result = await response.json();

            if (response.ok && result.status === 'success') {
                window.location.assign(result.redirect || '../../dashboard.php');
                return;
            }

            message.textContent = result.message || 'Login gagal. Silakan coba lagi.';
            message.className = 'login-password-error';
            password.classList.add('is-invalid');
        } catch (error) {
            message.textContent = 'Tidak dapat terhubung ke server. Coba lagi.';
            message.className = 'login-password-error';
            password.classList.add('is-invalid');
        } finally {
            submitButton.disabled = false;
        }
    });

}


/* =====================================================
   REGISTER
===================================================== */

const registerForm = document.getElementById('registerForm');

if (registerForm) {

    registerForm.addEventListener('submit', async function (event) {

        event.preventDefault();

        const email = document.getElementById('registerIdentity');
        const password = document.getElementById('registerPassword');
        const passwordFeedback =
            password.closest('.form-group').querySelector('.invalid-feedback');
        const confirmPassword = document.getElementById('confirmPassword');
        const confirmPasswordError =
            document.getElementById('confirmPasswordError');
        const message = document.getElementById('registerMessage');
        const submitButton = registerForm.querySelector('[type="submit"]');

        let isValid = true;

        if (!email.validity.valid || email.value.trim() === '') {
            email.classList.add('is-invalid');
            email.classList.remove('is-valid');
            isValid = false;
        } else {
            email.classList.remove('is-invalid');
            email.classList.add('is-valid');
        }

        if (password.value.length < 8) {
            password.classList.add('is-invalid');
            password.classList.remove('is-valid');
            passwordFeedback.textContent =
                'Password harus terdiri dari minimal 8 karakter.';
            passwordFeedback.classList.add('d-block');
            isValid = false;
        } else {
            password.classList.remove('is-invalid');
            password.classList.add('is-valid');
            passwordFeedback.classList.remove('d-block');
        }

        if (confirmPassword.value === '') {
            confirmPassword.classList.add('is-invalid');
            confirmPassword.classList.remove('is-valid');
            confirmPasswordError.textContent = 'Verifikasi password wajib diisi.';
            confirmPasswordError.classList.add('d-block');
            isValid = false;
        } else if (password.value !== confirmPassword.value) {
            confirmPassword.classList.add('is-invalid');
            confirmPassword.classList.remove('is-valid');
            confirmPasswordError.textContent = 'Password tidak sama.';
            confirmPasswordError.classList.add('d-block');
            isValid = false;
        } else {
            confirmPassword.classList.remove('is-invalid');
            confirmPassword.classList.add('is-valid');
            confirmPasswordError.classList.remove('d-block');
        }

        if (!isValid) return;

        message.classList.add('d-none');
        submitButton.disabled = true;

        try {
            const response = await fetch(registerForm.action, {
                method: 'POST',
                body: new FormData(registerForm),
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            });
            const result = await response.json();

            if (response.ok && result.status === 'success') {
                window.location.assign('Login.php');
                return;
            }

            message.textContent = result.message || 'Pendaftaran gagal.';
            message.className = 'auth-inline-error';
        } catch (error) {
            message.textContent = 'Tidak dapat terhubung ke server. Coba lagi.';
            message.className = 'auth-inline-error';
        } finally {
            submitButton.disabled = false;
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
                window.location.assign('../../Backend/api/google_login.php');

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

        if (this.id === 'registerPassword') {
            if (this.value.length >= 8) {
                this.classList.remove('is-invalid');
            }

            return;
        }

        if (this.value.trim() !== '') {

            this.classList.remove('is-invalid');

        }

    });

});
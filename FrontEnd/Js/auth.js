
/* =====================================================
   HELPER VALIDASI
===================================================== */

function showMessage(element, message, type = 'danger') {
    if (!element) return;

    element.textContent = message;
    element.className = `alert alert-${type} mt-3`;
    element.setAttribute('role', 'alert');
}

function hideMessage(element) {
    if (!element) return;

    element.textContent = '';
    element.classList.add('d-none');
}

function setInvalid(input, feedback, message) {
    if (input) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
    }

    if (feedback) {
        feedback.textContent = message;
        feedback.classList.add('d-block');
    }
}

function setValid(input, feedback) {
    if (input) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    }

    if (feedback) {
        feedback.textContent = '';
        feedback.classList.remove('d-block');
    }
}

function clearValidation(input) {
    if (!input) return;

    input.classList.remove('is-invalid', 'is-valid');

    const feedback = input
        .closest('.form-group')
        ?.querySelector('.invalid-feedback');

    if (feedback) {
        feedback.textContent = '';
        feedback.classList.remove('d-block');
    }
}

function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

function isValidPhone(value) {
    const phone = value.replace(/[\s()-]/g, '');

    return /^(?:\+62|62|0)8[1-9][0-9]{6,11}$/.test(phone);
}

async function sendForm(form) {
    const response = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            Accept: 'application/json'
        },
        credentials: 'same-origin'
    });

    let result;

    try {
        result = await response.json();
    } catch {
        throw new Error('server_error');
    }

    if (!response.ok && !result.status) {
        throw new Error('server_error');
    }

    return result;
}

function showSuccessModal(title, description, onConfirm) {
    const modal = document.getElementById('authSuccessModal');
    const titleElement = document.getElementById('authSuccessTitle');
    const descriptionElement = document.getElementById(
        'authSuccessDescription'
    );
    const confirmButton = document.getElementById('authSuccessConfirm');

    titleElement.textContent = title;
    descriptionElement.textContent = description;
    modal.hidden = false;

    confirmButton.onclick = function () {
        modal.hidden = true;
        onConfirm();
    };

    confirmButton.focus();
}

function setFormSubmitting(form, button, submitting, idleLabel) {
    form.setAttribute('aria-busy', String(submitting));
    button.disabled = submitting;
    button.textContent = submitting ? 'Memproses...' : idleLabel;
}


/* =====================================================
   PASSWORD SHOW / HIDE
===================================================== */

document.querySelectorAll('.password-toggle').forEach(button => {
    button.addEventListener('click', function () {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const icon = this.querySelector('i');

        if (!input || !icon) return;

        const show = input.type === 'password';

        input.type = show ? 'text' : 'password';

        icon.classList.toggle('bi-eye', show);
        icon.classList.toggle('bi-eye-slash', !show);

        this.setAttribute(
            'aria-label',
            show ? 'Sembunyikan password' : 'Tampilkan password'
        );
    });
});


/* =====================================================
   LOGIN
===================================================== */

const loginForm = document.getElementById('loginForm');

if (loginForm) {
    const identity = document.getElementById('loginIdentity');
    const password = document.getElementById('loginPassword');
    const message = document.getElementById('loginMessage');
    const submitButton = loginForm.querySelector('[type="submit"]');
    const submitLabel = submitButton.textContent.trim();
    let isSubmitting = false;

    const identityFeedback = identity
        .closest('.form-group')
        .querySelector('.invalid-feedback');

    const passwordFeedback = password
        .closest('.form-group')
        .querySelector('.invalid-feedback');

    const googleResult = new URLSearchParams(
        window.location.search
    ).get('google');

    const googleMessages = {
        success: 'Login Google berhasil.',
        not_configured: 'Login Google belum dikonfigurasi di server.',
        cancelled: 'Login Google dibatalkan.'
    };

    if (googleMessages[googleResult]) {
        showMessage(
            message,
            googleMessages[googleResult],
            googleResult === 'success' ? 'success' : 'danger'
        );
    }

    loginForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (isSubmitting) return;

        hideMessage(message);

        const identityValue = identity.value.trim();
        const passwordValue = password.value;

        let valid = true;
        const errors = [];

        if (!identityValue) {
            const error = 'Email atau nomor telepon wajib diisi.';

            setInvalid(identity, identityFeedback, error);
            errors.push(error);
            valid = false;
        } else if (
            identityValue.includes('@')
                ? !isValidEmail(identityValue)
                : !isValidPhone(identityValue)
        ) {
            const error = identityValue.includes('@')
                ? 'Masukkan alamat email yang valid.'
                : 'Masukkan nomor telepon yang valid.';

            setInvalid(identity, identityFeedback, error);
            errors.push(error);
            valid = false;
        } else {
            setValid(identity, identityFeedback);
        }

        if (!passwordValue.trim()) {
            const error = 'Password wajib diisi.';

            setInvalid(password, passwordFeedback, error);
            errors.push(error);
            valid = false;
        } else {
            setValid(password, passwordFeedback);
        }

        if (!valid) {
            showMessage(message, errors.join(' '));
            return;
        }

        isSubmitting = true;
        setFormSubmitting(loginForm, submitButton, true, submitLabel);

        try {
            const result = await sendForm(loginForm);

            if (result.status === 'success') {
                showSuccessModal(
                    'Login Berhasil!',
                    'Selamat datang kembali di Explore MajaKu',
                    function () {
                        window.location.assign(
                            result.redirect || '../index.html'
                        );
                    }
                );
                return;
            }

            const messages = {
                account_not_found:
                    'Akun tidak ditemukan. Silakan daftar terlebih dahulu.',

                wrong_password:
                    'Password yang kamu masukkan salah. Silakan coba lagi.',

                invalid_email:
                    'Masukkan alamat email yang valid.',

                invalid_phone:
                    'Masukkan nomor telepon yang valid.',

                validation_error:
                    result.message || 'Data login tidak valid.',

                server_error:
                    'Terjadi kesalahan pada server. Silakan coba lagi.'
            };

            const error = messages[result.status] ||
                result.message ||
                'Terjadi kesalahan pada server. Silakan coba lagi.';

            if (result.status === 'wrong_password') {
                setInvalid(password, passwordFeedback, error);
            } else if (result.status === 'account_not_found') {
                setInvalid(identity, identityFeedback, error);
            }

            showMessage(message, error);

        } catch {
            showMessage(
                message,
                'Terjadi kesalahan pada server. Silakan coba lagi.'
            );
        } finally {
            isSubmitting = false;
            setFormSubmitting(loginForm, submitButton, false, submitLabel);
        }
    });
}


/* =====================================================
   REGISTER
===================================================== */

const registerForm = document.getElementById('registerForm');

if (registerForm) {
    const email = document.getElementById('registerIdentity');
    const password = document.getElementById('registerPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const message = document.getElementById('registerMessage');
    const submitButton = registerForm.querySelector('[type="submit"]');
    const submitLabel = submitButton.textContent.trim();
    let isSubmitting = false;

    const emailFeedback = email
        .closest('.form-group')
        .querySelector('.invalid-feedback');

    const passwordFeedback = password
        .closest('.form-group')
        .querySelector('.invalid-feedback');

    const confirmFeedback = document.getElementById(
        'confirmPasswordError'
    );

    registerForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (isSubmitting) return;

        hideMessage(message);

        const emailValue = email.value.trim();
        const passwordValue = password.value;
        const confirmValue = confirmPassword.value;

        let valid = true;
        const errors = [];

        if (!emailValue) {
            const error = 'Email wajib diisi.';

            setInvalid(email, emailFeedback, error);
            errors.push(error);
            valid = false;
        } else if (!isValidEmail(emailValue)) {
            const error = 'Masukkan alamat email yang valid.';

            setInvalid(email, emailFeedback, error);
            errors.push(error);
            valid = false;
        } else {
            setValid(email, emailFeedback);
        }

        if (!passwordValue.trim()) {
            const error = 'Password wajib diisi.';

            setInvalid(password, passwordFeedback, error);
            errors.push(error);
            valid = false;
        } else if (passwordValue.length < 8) {
            const error =
                'Password harus terdiri dari minimal 8 karakter.';

            setInvalid(password, passwordFeedback, error);
            errors.push(error);
            valid = false;
        } else {
            setValid(password, passwordFeedback);
        }

        if (!confirmValue.trim()) {
            const error = 'Verifikasi password wajib diisi.';

            setInvalid(confirmPassword, confirmFeedback, error);
            errors.push(error);
            valid = false;
        } else if (passwordValue !== confirmValue) {
            const error = 'Konfirmasi password tidak sesuai.';

            setInvalid(confirmPassword, confirmFeedback, error);
            errors.push(error);
            valid = false;
        } else {
            setValid(confirmPassword, confirmFeedback);
        }

        if (!valid) {
            showMessage(message, errors.join(' '));
            return;
        }

        isSubmitting = true;
        setFormSubmitting(registerForm, submitButton, true, submitLabel);

        try {
            const result = await sendForm(registerForm);

            if (result.status === 'success') {
                showSuccessModal(
                    'Register Berhasil!',
                    'Akun kamu berhasil dibuat. Silakan Login untuk melanjutkan',
                    function () {
                        window.location.assign('Login.php');
                    }
                );
                return;
            }

            const messages = {
                email_exists:
                    'Email sudah terdaftar. Silakan gunakan email lain.',

                validation_error:
                    result.message || 'Data pendaftaran tidak valid.',

                server_error:
                    'Terjadi kesalahan pada server. Silakan coba lagi.'
            };

            const error = messages[result.status] ||
                result.message ||
                'Terjadi kesalahan pada server. Silakan coba lagi.';

            if (result.status === 'email_exists') {
                setInvalid(email, emailFeedback, error);
            }

            showMessage(message, error);

        } catch {
            showMessage(
                message,
                'Terjadi kesalahan pada server. Silakan coba lagi.'
            );
        } finally {
            isSubmitting = false;
            setFormSubmitting(registerForm, submitButton, false, submitLabel);
        }
    });
}


/* =====================================================
   GOOGLE LOGIN / REGISTER
===================================================== */

[
    document.getElementById('googleLogin'),
    document.getElementById('googleRegister')
].forEach(button => {
    if (button) {
        button.addEventListener('click', function () {
            window.location.assign(
                '../../Backend/api/google_login.php'
            );
        });
    }
});


/* =====================================================
   APPLE LOGIN / REGISTER
===================================================== */

[
    document.getElementById('appleLogin'),
    document.getElementById('appleRegister')
].forEach(button => {
    if (button) {
        button.addEventListener('click', function () {
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
   HAPUS VALIDASI KETIKA INPUT DIUBAH
===================================================== */

document.querySelectorAll('.auth-input').forEach(input => {
    input.addEventListener('input', function () {
        clearValidation(this);

        if (this.id === 'registerPassword') {
            const confirmPassword =
                document.getElementById('confirmPassword');

            if (confirmPassword && confirmPassword.value !== '') {
                clearValidation(confirmPassword);
            }
        }
    });
});

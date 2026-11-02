
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
        input.setAttribute('aria-invalid', 'true');
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
        input.setAttribute('aria-invalid', 'false');
    }

    if (feedback) {
        feedback.textContent = '';
        feedback.classList.remove('d-block');
    }
}

function clearValidation(input) {
    if (!input) return;

    input.classList.remove('is-invalid', 'is-valid');
    input.removeAttribute('aria-invalid');

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

function isValidRegisterPassword(value) {
    return value.length >= 8 &&
        new TextEncoder().encode(value).length <= 72 &&
        /[a-z]/.test(value) &&
        /[A-Z]/.test(value) &&
        /\d/.test(value) &&
        /[^A-Za-z0-9]/.test(value);
}

function isValidPhone(value) {
    const phone = value.replace(/[\s()-]/g, '');

    return /^\+628[1-9][0-9]{6,11}$/.test(phone);
}

function isValidLoginPhone(value) {
    const phone = value.replace(/[\s()-]/g, '');

    return /^\+628[1-9][0-9]{6,11}$/.test(phone);
}

async function sendForm(form) {
    const endpoint = new URL(form.action, window.location.href).pathname;
    console.info(`[API Request] POST ${endpoint}`);

    let response;

    try {
        response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                Accept: 'application/json'
            },
            credentials: 'same-origin'
        });
    } catch (error) {
        console.error(`[API Error] ${endpoint}`, error);
        throw error;
    }

    let result;

    try {
        result = await response.json();
    } catch (error) {
        console.info(`[API Response] ${endpoint}`, {
            httpStatus: response.status,
            ok: response.ok
        });
        console.error(`[API Error] ${endpoint}`, error);
        throw new Error('server_error');
    }

    console.info(`[API Response] ${endpoint}`, {
        httpStatus: response.status,
        ok: response.ok,
        status: result.status,
        message: result.message,
        role: result.data?.role
    });

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

document.addEventListener('click', event => {
    const button = event.target.closest('.password-toggle');
    if (!button) return;

    const input = document.getElementById(button.dataset.target);
    if (!input) return;

    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';

    const icon = button.querySelector('i');
    if (icon) {
        icon.classList.toggle('bi-eye', show);
        icon.classList.toggle('bi-eye-slash', !show);
    }

    button.setAttribute('aria-pressed', String(show));
    button.setAttribute(
        'aria-label',
        show ? 'Sembunyikan password' : 'Tampilkan password'
    );
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

        if (!identityValue) {
            setInvalid(
                identity,
                identityFeedback,
                'Email/No telepon wajib diisi'
            );
            valid = false;
        } else if (
            identityValue.includes('@')
                ? !isValidEmail(identityValue)
                : !isValidLoginPhone(identityValue)
        ) {
            setInvalid(
                identity,
                identityFeedback,
                'Masukkan alamat email / no telepon yang valid'
            );
            valid = false;
        } else {
            setValid(identity, identityFeedback);
        }

        if (!passwordValue.trim()) {
            setInvalid(password, passwordFeedback, 'Password wajib diisi');
            valid = false;
        } else {
            setValid(password, passwordFeedback);
        }

        if (!valid) {
            loginForm.querySelector('.is-invalid')?.focus();
            return;
        }

        isSubmitting = true;
        setFormSubmitting(loginForm, submitButton, true, submitLabel);

        try {
            const result = await sendForm(loginForm);

            if (result.status === 'success') {
                const loginParams = new URLSearchParams(window.location.search);
                const pendingQuery = (loginParams.get('q') || '').trim();
                const redirectTarget = loginParams.get('next') === 'search'
                    ? `../../search${pendingQuery ? `?q=${encodeURIComponent(pendingQuery)}` : ''}`
                    : (result.redirect || '../../index.php');

                showSuccessModal(
                    'Login Berhasil!',
                    'Selamat datang kembali di Explore MajaKu',
                    function () {
                        window.location.assign(redirectTarget);
                    }
                );
                return;
            }

            if (result.status === 'wrong_password') {
                setInvalid(
                    password,
                    passwordFeedback,
                    'Password yang kamu masukkan salah, silahkan coba lagi'
                );
            } else if (
                result.status === 'invalid_email' ||
                result.status === 'invalid_phone'
            ) {
                setInvalid(
                    identity,
                    identityFeedback,
                    result.status === 'invalid_email'
                        ? 'Masukkan alamat email yang valid'
                        : 'Masukkan nomor telepon yang valid'
                );
            } else {
                const messages = {
                    account_not_found:
                        'Email atau No Telepon tidak ditemukan, silahkan daftar terlebih dahulu!',
                    validation_error: result.message ||
                        'Data login tidak valid.',
                    server_error:
                        'Terjadi kesalahan server, silahkan coba beberapa saat lagi.'
                };

                showMessage(
                    message,
                    messages[result.status] ||
                        result.message ||
                        'Terjadi kesalahan server, silahkan coba beberapa saat lagi.'
                );
            }

        } catch {
            showMessage(
                message,
                'Terjadi kesalahan server. Silakan coba lagi beberapa saat.'
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
    const identity = document.getElementById('registerIdentity');
    const password = document.getElementById('registerPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const message = document.getElementById('registerMessage');
    const submitButton = registerForm.querySelector('[type="submit"]');
    const submitLabel = submitButton.textContent.trim();
    let isSubmitting = false;

    const identityFeedback = identity
        .closest('.form-group')
        .querySelector('.invalid-feedback');

    const passwordFeedback = password
        .closest('.form-group')
        .querySelector('.invalid-feedback');

    const confirmFeedback = document.getElementById(
        'confirmPasswordError'
    );

    const googleResult = new URLSearchParams(
        window.location.search
    ).get('google');
    const googleMessages = {
        email_exists:
            'Email Google ini sudah terdaftar. Silakan login dengan email tersebut.',
        not_configured: 'Login Google belum dikonfigurasi di server.',
        cancelled: 'Pendaftaran dengan Google dibatalkan.'
    };

    if (googleResult === 'email_exists') {
        setInvalid(identity, identityFeedback, googleMessages[googleResult]);
    } else if (googleMessages[googleResult]) {
        showMessage(message, googleMessages[googleResult]);
    }

    registerForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (isSubmitting) return;

        hideMessage(message);

        const identityValue = identity.value.trim();
        const passwordValue = password.value;
        const confirmValue = confirmPassword.value;

        let valid = true;

        if (!identityValue) {
            setInvalid(
                identity,
                identityFeedback,
                'Email/No telepon wajib di isi'
            );
            valid = false;
        } else if (
            identityValue.includes('@')
                ? !isValidEmail(identityValue)
                : !isValidPhone(identityValue)
        ) {
            setInvalid(
                identity,
                identityFeedback,
                'Masukkan alamat email / no telepon yang valid'
            );
            valid = false;
        } else {
            setValid(identity, identityFeedback);
        }

        if (!passwordValue.trim()) {
            setInvalid(
                password,
                passwordFeedback,
                'Password wajib diisi'
            );
            valid = false;
        } else if (!isValidRegisterPassword(passwordValue)) {
            setInvalid(
                password,
                passwordFeedback,
                'Password minimal 8 karakter dan harus memuat huruf besar, huruf kecil, angka, serta karakter khusus.'
            );
            valid = false;
        } else {
            setValid(password, passwordFeedback);
        }

        if (!confirmValue.trim()) {
            setInvalid(
                confirmPassword,
                confirmFeedback,
                'Verifikasi password wajib diisi'
            );
            valid = false;
        } else if (passwordValue !== confirmValue) {
            setInvalid(
                confirmPassword,
                confirmFeedback,
                'Konfirmasi password tidak sesuai'
            );
            valid = false;
        } else {
            setValid(confirmPassword, confirmFeedback);
        }

        if (!valid) {
            registerForm.querySelector('.is-invalid')?.focus();
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

            if (
                result.status === 'email_exists' ||
                result.status === 'phone_exists'
            ) {
                setInvalid(
                    identity,
                    identityFeedback,
                    'Email/ No Telepon sudah terdaftar, silahkan gunakan email/no telp lain'
                );
            } else if (result.status === 'validation_error') {
                const errorMessage = result.message ||
                    'Data pendaftaran tidak valid.';
                const normalizedMessage = errorMessage.toLowerCase();

                if (normalizedMessage.includes('password')) {
                    setInvalid(password, passwordFeedback, errorMessage);
                } else if (normalizedMessage.includes('verifikasi')) {
                    setInvalid(
                        confirmPassword,
                        confirmFeedback,
                        errorMessage
                    );
                } else {
                    setInvalid(identity, identityFeedback, errorMessage);
                }
            } else {
                const messages = {
                    phone_not_configured:
                        result.message ||
                        'Pendaftaran dengan nomor telepon belum tersedia di server.',
                    server_error:
                        'Terjadi kesalahan server, silahkan coba beberapa saat lagi.'
                };

                showMessage(
                    message,
                    messages[result.status] ||
                        result.message ||
                        'Terjadi kesalahan server, silahkan coba beberapa saat lagi.'
                );
            }

        } catch {
            showMessage(
                message,
                'Terjadi kesalahan server, silahkan coba beberapa saat lagi.'
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
            const intent = this.id === 'googleRegister' ? 'register' : 'login';
            window.location.assign(
                `../../Backend/api/google_login.php?intent=${intent}`
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

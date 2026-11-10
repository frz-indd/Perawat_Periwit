const loginForm = document.getElementById('loginForm');

if (loginForm) {
    const identity = document.getElementById('loginIdentity');
    const password = document.getElementById('loginPassword');
    const message = document.getElementById('loginMessage');
    const submitButton = loginForm.querySelector('[type="submit"]');
    const submitLabel = submitButton.textContent.trim();
    const identityFeedback = identity
        .closest('.form-group')
        .querySelector('.invalid-feedback');
    const passwordFeedback = password
        .closest('.form-group')
        .querySelector('.invalid-feedback');
    let isSubmitting = false;

    const googleResult = new URLSearchParams(
        window.location.search
    ).get('google');
    const googleMessages = {
        success: 'Login Google berhasil.',
        not_configured: 'Login Google belum dikonfigurasi di server.',
        cancelled: 'Login Google dibatalkan.'
    };

    if (googleMessages[googleResult]) {
        Auth.showMessage(
            message,
            googleMessages[googleResult],
            googleResult === 'success' ? 'success' : 'danger'
        );
    }

    loginForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (isSubmitting) return;

        Auth.hideMessage(message);

        const identityValue = identity.value.trim();
        const passwordValue = password.value;
        let valid = true;

     if (!identityValue) {
            Auth.setInvalid(
                identity,
                identityFeedback,
                'Email/No telepon wajib di isi.'
            );
            valid = false;
        } else {
            // Cek apakah input berupa angka (nomor telepon) atau teks/email
            const isNumber = /^[0-9+]+$/.test(identityValue); // Hanya berisi angka atau tanda +

            if (isNumber) {
                // Jika isinya angka, validasi sebagai nomor telepon
                if (!Auth.isValidPhone(identityValue)) {
                    Auth.setInvalid(identity, identityFeedback, 'Masukkan nomor telepon yang valid.');
                    valid = false;
                } else {
                    Auth.setValid(identity, identityFeedback);
                }
            } else {
                // Jika isinya ada huruf/karakter lain (bukan murni angka), validasi sebagai email
                if (!Auth.isValidEmail(identityValue)) {
                    Auth.setInvalid(identity, identityFeedback, 'Masukkan alamat email yang valid.');
                    valid = false;
                } else {
                    Auth.setValid(identity, identityFeedback);
                }
            }
        }

        if (!passwordValue.trim()) {
            Auth.setInvalid(
                password,
                passwordFeedback,
                'Password wajib di isi.'
            );
            valid = false;
        } else {
            Auth.setValid(password, passwordFeedback);
        }

        if (!valid) return;

        isSubmitting = true;
        Auth.setFormSubmitting(loginForm, submitButton, true, submitLabel);

        try {
            const result = await Auth.sendForm(loginForm);

            if (result.status === 'success') {
                Auth.showSuccessModal(
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

            if (result.status === 'account_not_found') {
                Auth.setInvalid(
                    identity,
                    identityFeedback,
                    'Akun tidak ditemukan. Silakan daftar terlebih dahulu.'
                );
                return;
            }

            if (result.status === 'wrong_password') {
                Auth.setInvalid(
                    password,
                    passwordFeedback,
                    'Password yang kamu masukkan salah, silahkan coba lagi.'
                );
                return;
            }

            if (result.status === 'validation_error') {
                const error = result.message || 'Data login tidak valid.';

                if (/password/i.test(error)) {
                    Auth.setInvalid(password, passwordFeedback, error);
                } else if (/email|telepon/i.test(error)) {
                    Auth.setInvalid(identity, identityFeedback, error);
                } else {
                    Auth.showMessage(message, error);
                }
                return;
            }

            Auth.showMessage(
                message,
                'Terjadi kesalahan server. Silakan coba lagi beberapa saat.'
            );
        } catch {
            Auth.showMessage(
                message,
                'Terjadi kesalahan server. Silakan coba lagi beberapa saat.'
            );
        } finally {
            isSubmitting = false;
            Auth.setFormSubmitting(loginForm, submitButton, false, submitLabel);
        }
    });
}

const registerForm = document.getElementById('registerForm');

if (registerForm) {
    const identity = document.getElementById('registerIdentity');
    const password = document.getElementById('registerPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const message = document.getElementById('registerMessage');
    const submitButton = registerForm.querySelector('[type="submit"]');
    const submitLabel = submitButton.textContent.trim();
    const identityFeedback = identity
        .closest('.form-group')
        .querySelector('.invalid-feedback');
    const passwordFeedback = password
        .closest('.form-group')
        .querySelector('.invalid-feedback');
    const confirmFeedback = document.getElementById('confirmPasswordError');
    let isSubmitting = false;

    const googleResult = new URLSearchParams(
        window.location.search
    ).get('google');
    const googleMessages = {
        email_exists:
            'Email Google ini sudah terdaftar. Silakan login dengan email tersebut.',
        not_configured: 'Login Google belum dikonfigurasi di server.',
        cancelled: 'Pendaftaran dengan Google dibatalkan.'
    };

    if (googleMessages[googleResult]) {
        Auth.showMessage(message, googleMessages[googleResult]);
    }

    registerForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (isSubmitting) return;

        Auth.hideMessage(message);

        const identityValue = identity.value.trim();
        const passwordValue = password.value;
        const confirmValue = confirmPassword.value;
        let valid = true;

        if (!identityValue) {
            Auth.setInvalid(
                identity,
                identityFeedback,
                'Email/No telepon wajib di isi.'
            );
            valid = false;
        } else if (
            identityValue.includes('@')
                ? !Auth.isValidEmail(identityValue)
                : !Auth.isValidPhone(identityValue)
        ) {
            Auth.setInvalid(
                identity,
                identityFeedback,
                'Masukkan alamat email / no telepon yang valid.'
            );
            valid = false;
        } else {
            Auth.setValid(identity, identityFeedback);
        }

        if (!passwordValue.trim()) {
            Auth.setInvalid(
                password,
                passwordFeedback,
                'Password wajib di isi.'
            );
            valid = false;
        } else if (passwordValue.length < 8) {
            Auth.setInvalid(
                password,
                passwordFeedback,
                'Password harus terdiri dari minimal 8 karakter.'
            );
            valid = false;
        } else {
            Auth.setValid(password, passwordFeedback);
        }

        if (!confirmValue.trim()) {
            Auth.setInvalid(
                confirmPassword,
                confirmFeedback,
                'Verifikasi password wajib di isi.'
            );
            valid = false;
        } else if (passwordValue !== confirmValue) {
            Auth.setInvalid(
                confirmPassword,
                confirmFeedback,
                'Konfirmasi password tidak sesuai.'
            );
            valid = false;
        } else {
            Auth.setValid(confirmPassword, confirmFeedback);
        }

        if (!valid) return;

        isSubmitting = true;
        Auth.setFormSubmitting(registerForm, submitButton, true, submitLabel);

        try {
            const result = await Auth.sendForm(registerForm);

            if (result.status === 'success') {
                Auth.showSuccessModal(
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
                Auth.setInvalid(
                    identity,
                    identityFeedback,
                    result.message ||
                        'Email/No telepon sudah terdaftar. Silakan gunakan yang lain.'
                );
                return;
            }

            if (result.status === 'validation_error') {
                const error = result.message || 'Data pendaftaran tidak valid.';

                if (result.field === 'identity') {
                    Auth.setInvalid(identity, identityFeedback, error);
                } else if (result.field === 'password') {
                    Auth.setInvalid(password, passwordFeedback, error);
                } else if (result.field === 'confirmPassword') {
                    Auth.setInvalid(confirmPassword, confirmFeedback, error);
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
            Auth.setFormSubmitting(
                registerForm,
                submitButton,
                false,
                submitLabel
            );
        }
    });
}

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

function normalizePhone(value) {
    const phone = value.replace(/[\s()-]/g, '');

    if (phone.startsWith('0')) {
        return `+62${phone.slice(1)}`;
    }

    if (phone.startsWith('62')) {
        return `+${phone}`;
    }

    return phone;
}

function isValidPhone(value) {
    const phone = normalizePhone(value);
    return /^\+628[1-9][0-9]{6,11}$/.test(phone);
}

async function sendForm(form) {
    const endpoint = new URL(form.action, window.location.href).pathname;
    const formData = new FormData(form);
    const identity = formData.get('identity');

    if (typeof identity === 'string' && !identity.includes('@')) {
        formData.set('identity', normalizePhone(identity));
    }

    console.info(`[API Request] POST ${endpoint}`);

    let response;

    try {
        response = await fetch(form.action, {
            method: 'POST',
            body: formData,
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
        message: result.message
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

    if (!modal || !titleElement || !descriptionElement || !confirmButton) {
        throw new Error('Komponen dialog sukses tidak ditemukan.');
    }

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

window.Auth = {
    clearValidation,
    hideMessage,
    isValidEmail,
    isValidPhone,
    sendForm,
    setFormSubmitting,
    setInvalid,
    setValid,
    showMessage,
    showSuccessModal
};

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

const forgotPassword = document.querySelector('.forgot-password');

if (forgotPassword) {
    forgotPassword.addEventListener('click', function (event) {
        event.preventDefault();
        alert('Halaman lupa password belum dibuat.');
    });
}

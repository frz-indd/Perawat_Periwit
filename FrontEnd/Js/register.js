document.getElementById('togglePassword').addEventListener('click', function () {
    const pass = document.getElementById('passwordInput');
    const eye = document.getElementById('eyePass');
    pass.type = pass.type === 'password' ? 'text' : 'password';
    eye.classList.toggle('bi-eye'); eye.classList.toggle('bi-eye-slash');
});

document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
    const pass = document.getElementById('confirmPasswordInput');
    const eye = document.getElementById('eyeConfirm');
    pass.type = pass.type === 'password' ? 'text' : 'password';
    eye.classList.toggle('bi-eye'); eye.classList.toggle('bi-eye-slash');
});

document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const emailInput = document.getElementById('emailInput');
    const passwordInput = document.getElementById('passwordInput');
    const confirmPasswordInput = document.getElementById('confirmPasswordInput');

    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');

    emailError.innerText = ''; passwordError.innerText = ''; confirmPasswordError.innerText = '';
    emailInput.classList.remove('is-invalid-custom');
    passwordInput.classList.remove('is-invalid-custom');
    confirmPasswordInput.classList.remove('is-invalid-custom');

    let isValid = true;
    const emailVal = emailInput.value.trim();
    const passVal = passwordInput.value;
    const confirmVal = confirmPasswordInput.value;

    if (!emailVal) {
        emailError.innerText = 'Email/No telepone wajib di isi';
        emailInput.classList.add('is-invalid-custom');
        isValid = false;
    } else if (!emailVal.includes('@') && isNaN(emailVal)) {
        emailError.innerText = 'Masukkan alamat email / no telepon yang valid';
        emailInput.classList.add('is-invalid-custom');
        isValid = false;
    }

    if (!passVal) {
        passwordError.innerText = 'Password wajib di isi';
        passwordInput.classList.add('is-invalid-custom');
        isValid = false;
    } else if (passVal.length < 8) {
        passwordError.innerText = 'Password harus terdiri dari minimal 8 karakter';
        passwordInput.classList.add('is-invalid-custom');
        isValid = false;
    }

    if (!confirmVal) {
        confirmPasswordError.innerText = 'Verifikasi password wajib di isi';
        confirmPasswordInput.classList.add('is-invalid-custom');
        isValid = false;
    } else if (passVal !== confirmVal) {
        confirmPasswordError.innerText = 'Konfirmasi password tidak sesuai';
        confirmPasswordInput.classList.add('is-invalid-custom');
        isValid = false;
    }

    if (isValid) {
        // Integrasi API: POST /Backend/api/fungsi_register.php
        try {
            const formData = new URLSearchParams();
            formData.append('identity', emailVal);
            formData.append('password', passVal);
            formData.append('confirmPassword', confirmVal);

            const response = await fetch('/Backend/api/fungsi_register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });

            if (response.status === 201) {
                const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
            } else if (response.status === 409) {
                emailError.innerText = 'Email/ No Telepon sudah terdaftar. silahkan gunakan email/ No telp lain';
                emailInput.classList.add('is-invalid-custom');
            } else {
                const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
            }
        } catch (error) {
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
        }
    }
});

function redirectToLogin() {
    window.location.href = 'login.html';
}
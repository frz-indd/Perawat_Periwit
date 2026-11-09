document.getElementById('toggleLoginPassword').addEventListener('click', function () {
    const pass = document.getElementById('loginPasswordInput');
    const eye = document.getElementById('eyeLoginPass');
    pass.type = pass.type === 'password' ? 'text' : 'password';
    eye.classList.toggle('bi-eye');
    eye.classList.toggle('bi-eye-slash');
});

document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const emailInput = document.getElementById('loginEmailInput');
    const passwordInput = document.getElementById('loginPasswordInput');
    const emailError = document.getElementById('loginEmailError');
    const passwordError = document.getElementById('loginPasswordError');

    emailError.innerText = '';
    passwordError.innerText = '';
    emailInput.classList.remove('is-invalid-custom');
    passwordInput.classList.remove('is-invalid-custom');

    let isValid = true;
    const emailVal = emailInput.value.trim();
    const passVal = passwordInput.value;

    if (!emailVal) {
        emailError.innerText = 'Email/No telefone wajib di isi';
        emailInput.classList.add('is-invalid-custom');
        isValid = false;
    } else if (!emailVal.includes('@') && isNaN(emailVal)) {
        emailError.innerText = 'Masukkan alamat email yang valid';
        emailInput.classList.add('is-invalid-custom');
        isValid = false;
    }

    if (!passVal) {
        passwordError.innerText = 'Password wajib di isi';
        passwordInput.classList.add('is-invalid-custom');
        isValid = false;
    }

    if (isValid) {
        // Integrasi API: POST /Backend/api/fungsi_login.php
        try {
            const formData = new URLSearchParams();
            formData.append('email', emailVal);
            formData.append('password', passVal);

            const response = await fetch('/Backend/api/fungsi_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });

            if (response.status === 200) {
                const successModal = new bootstrap.Modal(document.getElementById('loginSuccessModal'));
                successModal.show();
            } else if (response.status === 401) {
                passwordError.innerText = 'Password yang kamu masukkan salah, silahkan coba lagi';
                passwordInput.classList.add('is-invalid-custom');
            } else if (response.status === 404) {
                emailError.innerText = 'Akun tidak ditemukan. Silahkan daftar terlebih dahulu.';
                emailInput.classList.add('is-invalid-custom');
            } else if (response.status === 500) {
                passwordError.innerText = 'Terjadi kesalahan server. Silahkan coba lagi beberapa saat.';
                passwordInput.classList.add('is-invalid-custom');
            } else {
                // Fallback Simulasi Lokal jika backend belum aktif
                if (passVal !== 'password123') {
                    passwordError.innerText = 'Password yang kamu masukkan salah, silahkan coba lagi';
                    passwordInput.classList.add('is-invalid-custom');
                } else {
                    const successModal = new bootstrap.Modal(document.getElementById('loginSuccessModal'));
                    successModal.show();
                }
            }
        } catch (error) {
            // Fallback untuk demo lokal
            if (passVal !== 'password123') {
                passwordError.innerText = 'Password yang kamu masukkan salah, silahkan coba lagi';
                passwordInput.classList.add('is-invalid-custom');
            } else {
                const successModal = new bootstrap.Modal(document.getElementById('loginSuccessModal'));
                successModal.show();
            }
        }
    }
});

function redirectToHome() {
    window.location.href = 'index.html';
}
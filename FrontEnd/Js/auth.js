/* =====================================================
   PASSWORD SHOW / HIDE
===================================================== */

const passwordToggleButtons =
    document.querySelectorAll('.password-toggle');

passwordToggleButtons.forEach(button => {

    button.addEventListener('click', function () {

        const targetId =
            this.getAttribute('data-target');

        const passwordInput =
            document.getElementById(targetId);

        const icon =
            this.querySelector('i');


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

const loginForm =
    document.getElementById('loginForm');

if (loginForm) {

    loginForm.addEventListener('submit', function (event) {

        event.preventDefault();

        const identity =
            document.getElementById('loginIdentity');

        const password =
            document.getElementById('loginPassword');

        let isValid = true;


        // Identity
        if (identity.value.trim() === '') {

            identity.classList.add('is-invalid');
            identity.classList.remove('is-valid');

            isValid = false;

        } else {

            identity.classList.remove('is-invalid');
            identity.classList.add('is-valid');

        }


        // Password
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

            alert('Login berhasil!');

            /*
            NANTI KALAU BACKEND SUDAH ADA,
            BAGIAN INI BISA DIARAHKAN KE BERANDA.

            window.location.href = "../beranda.html";
            */
        }

    });
}


/* =====================================================
   REGISTER
===================================================== */

const registerForm =
    document.getElementById('registerForm');

if (registerForm) {

    registerForm.addEventListener('submit', function (event) {

        event.preventDefault();


        const identity =
            document.getElementById('registerIdentity');

        const password =
            document.getElementById('registerPassword');

        const confirmPassword =
            document.getElementById('confirmPassword');

        const confirmPasswordError =
            document.getElementById('confirmPasswordError');


        let isValid = true;


        // Identity
        if (identity.value.trim() === '') {

            identity.classList.add('is-invalid');
            identity.classList.remove('is-valid');

            isValid = false;

        } else {

            identity.classList.remove('is-invalid');
            identity.classList.add('is-valid');

        }


        // Password
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


        // Confirm password
        if (confirmPassword.value.trim() === '') {

            confirmPassword.classList.add('is-invalid');
            confirmPassword.classList.remove('is-valid');

            confirmPasswordError.textContent =
                'Verifikasi password wajib diisi.';

            isValid = false;

        } else if (
            password.value !== confirmPassword.value
        ) {

            confirmPassword.classList.add('is-invalid');
            confirmPassword.classList.remove('is-valid');

            confirmPasswordError.textContent =
                'Password tidak sama.';

            isValid = false;

        } else {

            confirmPassword.classList.remove('is-invalid');
            confirmPassword.classList.add('is-valid');

        }


        // Jika valid
        if (isValid) {

            alert('Pendaftaran berhasil!');

            /*
            NANTI KALAU BACKEND SUDAH ADA,
            BISA DIARAHKAN KE LOGIN.

            window.location.href = "login.html";
            */
        }

    });
}


/* =====================================================
   GOOGLE
===================================================== */

const googleButtons = [

    document.getElementById('googleLogin'),

    document.getElementById('googleRegister')

];


googleButtons.forEach(button => {

    if (button) {

        button.addEventListener('click', function () {

            alert(
                'Login dengan Google belum dihubungkan.'
            );

        });

    }

});


/* =====================================================
   LUPA PASSWORD
===================================================== */

const forgotPassword =
    document.querySelector('.forgot-password');

if (forgotPassword) {

    forgotPassword.addEventListener(
        'click',
        function (event) {

            event.preventDefault();

            alert(
                'Halaman lupa password belum dibuat.'
            );

        }
    );

}


/* =====================================================
   HAPUS ERROR SAAT INPUT
===================================================== */

const allInputs =
    document.querySelectorAll('.auth-input');

allInputs.forEach(input => {

    input.addEventListener('input', function () {

        this.classList.remove('is-invalid');

    });

});
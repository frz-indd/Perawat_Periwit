
document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("loginForm");
    const identity = document.getElementById("identity");
    const password = document.getElementById("password");

    const identityError = document.getElementById("identityError");
    const passwordError = document.getElementById("passwordError");

    const loginButton = document.getElementById("loginButton");
    const alertBox = document.getElementById("alertBox");

    const API_URL = "../../Backend/api/fungsi_login.php";

    // Menampilkan pesan error
    function showError(input, errorElement, message) {
        errorElement.textContent = message;
        input.classList.toggle("invalid", message !== "");
    }

    // Menghapus pesan error
    function clearError(input, errorElement) {
        errorElement.textContent = "";
        input.classList.remove("invalid");
    }

    // Alert
    function showAlert(message, type = "error") {
        alertBox.textContent = message;
        alertBox.className = "alert-box show " + type;
    }

    function clearAlert() {
        alertBox.textContent = "";
        alertBox.className = "alert-box";
    }

    function showSuccessModal(title, description, onConfirm) {
        const modal = document.getElementById("authSuccessModal");
        const titleElement = document.getElementById("authSuccessTitle");
        const descriptionElement = document.getElementById(
            "authSuccessDescription"
        );
        const confirmButton = document.getElementById("authSuccessConfirm");

        titleElement.textContent = title;
        descriptionElement.textContent = description;
        modal.hidden = false;

        confirmButton.onclick = function () {
            modal.hidden = true;
            onConfirm();
        };

        confirmButton.focus();
    }

    // Validasi identitas
    function validateIdentity() {
        const value = identity.value.trim();

        if (value === "") {
            showError(
                identity,
                identityError,
                "Email/No telepon wajib di isi"
            );
            return false;
        }

        if (value.includes("@")) {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailPattern.test(value)) {
                showError(
                    identity,
                    identityError,
                    "Format email tidak valid"
                );
                return false;
            }
        }

        clearError(identity, identityError);
        return true;
    }

    // Validasi password
    function validatePassword() {
        const value = password.value;

        if (value.trim() === "") {
            showError(
                password,
                passwordError,
                "Password wajib di isi"
            );
            return false;
        }

        if (value.length < 8) {
            showError(
                password,
                passwordError,
                "Password harus terdiri dari minimal 8 karakter"
            );
            return false;
        }

        clearError(password, passwordError);
        return true;
    }

    // Validasi ketika input berubah
    identity.addEventListener("input", function () {
        clearAlert();
        if (identityError.textContent !== "") {
            validateIdentity();
        }
    });

    password.addEventListener("input", function () {
        clearAlert();
        if (passwordError.textContent !== "") {
            validatePassword();
        }
    });

    // Toggle password
    document.querySelectorAll(".toggle-password").forEach(button => {

        button.addEventListener("click", function () {

            const target = document.getElementById(
                this.dataset.target
            );

            if (target.type === "password") {
                target.type = "text";
                this.classList.add("active");
                this.setAttribute("aria-label", "Sembunyikan password");
            } else {
                target.type = "password";
                this.classList.remove("active");
                this.setAttribute("aria-label", "Tampilkan password");
            }
        });
    });

    // Submit login
    form.addEventListener("submit", async function (event) {

        event.preventDefault();
        clearAlert();

        const identityValid = validateIdentity();
        const passwordValid = validatePassword();

        if (!identityValid || !passwordValid) {
            return;
        }

        loginButton.disabled = true;
        loginButton.classList.add("loading");

        try {

            const formData = new FormData();

            formData.append("identity", identity.value.trim());
            formData.append("password", password.value);

            const response = await fetch(API_URL, {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            });

            const result = await response.json();

           if (result.status === "success") {
    const loginParams = new URLSearchParams(window.location.search);
    const pendingQuery = (loginParams.get('q') || '').trim();
    const redirectTarget = loginParams.get('next') === 'search'
        ? `../../search${pendingQuery ? `?q=${encodeURIComponent(pendingQuery)}` : ''}`
        : (result.redirect || '../../index.php');

    // === MASUKKAN / GUNAKAN DI SINI (UNTUK LOGIN) ===
    showSuccessModal(
        "Login Berhasil!",
        "Selamat datang kembali di Explore MajaKu",
        function () {
            window.location.assign(redirectTarget);
        }
    );
    return;
}

            if (
                result.status === "account_not_found" ||
                response.status === 404
            ) {
                showError(
                    identity,
                    identityError,
                    "Akun tidak ditemukan. Silakan daftar terlebih dahulu."
                );

            } else if (
                result.status === "wrong_password" ||
                response.status === 401
            ) {
                showError(
                    password,
                    passwordError,
                    "Password yang kamu masukkan salah. Silakan coba lagi."
                );

            } else if (result.status === "validation_error") {

                showAlert(result.message);

            } else {

                showAlert(
                    result.message ||
                    "Terjadi kesalahan pada server. Silakan coba lagi."
                );
            }

        } catch (error) {

            console.error("Login error:", error);

            showAlert(
                "Tidak dapat terhubung ke server. Silakan coba lagi."
            );

        } finally {

            loginButton.disabled = false;
            loginButton.classList.remove("loading");
        }
    });

});

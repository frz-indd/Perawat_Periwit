
document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("registerForm");

    const identity = document.getElementById("identity");
    const password = document.getElementById("password");
    const confirmPassword = document.getElementById("confirmPassword");

    const identityError = document.getElementById("identityError");
    const passwordError = document.getElementById("passwordError");
    const confirmPasswordError = document.getElementById("confirmPasswordError");

    const registerButton = document.getElementById("registerButton");
    const alertBox = document.getElementById("alertBox");

    const API_URL = "../../Backend/api/fungsi_register.php";

    // Menampilkan error
    function showError(input, errorElement, message) {
        errorElement.textContent = message;
        input.classList.toggle("invalid", message !== "");
    }

    // Menghapus error
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

    // Validasi email / nomor telepon
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

        } else {

            const phone = value.replace(/[\s()-]/g, "");

            const phonePattern = /^\+628[1-9][0-9]{6,11}$/;

            if (!phonePattern.test(phone)) {
                showError(
                    identity,
                    identityError,
                    "Nomor telepon harus menggunakan awalan +62"
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

    // Validasi konfirmasi password
    function validateConfirmPassword() {

        const value = confirmPassword.value;

        if (value.trim() === "") {
            showError(
                confirmPassword,
                confirmPasswordError,
                "Verifikasi password wajib di isi"
            );
            return false;
        }

        if (value !== password.value) {
            showError(
                confirmPassword,
                confirmPasswordError,
                "Konfirmasi password tidak sesuai"
            );
            return false;
        }

        clearError(confirmPassword, confirmPasswordError);
        return true;
    }

    // Input berubah
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

        if (confirmPassword.value !== "") {
            validateConfirmPassword();
        }
    });

    confirmPassword.addEventListener("input", function () {
        clearAlert();

        if (confirmPasswordError.textContent !== "") {
            validateConfirmPassword();
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

    // Submit register
    form.addEventListener("submit", async function (event) {

        event.preventDefault();
        clearAlert();

        const identityValid = validateIdentity();
        const passwordValid = validatePassword();
        const confirmValid = validateConfirmPassword();

        if (!identityValid || !passwordValid || !confirmValid) {
            return;
        }

        registerButton.disabled = true;
        registerButton.classList.add("loading");

        try {

            const formData = new FormData();

            formData.append("identity", identity.value.trim());
            formData.append("password", password.value);
            formData.append("confirmPassword", confirmPassword.value);

            const response = await fetch(API_URL, {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            });

            const result = await response.json();

            if (result.status === "success") {
    // === MASUKKAN / GUNAKAN DI SINI (UNTUK REGISTER) ===
    showSuccessModal(
        "Register Berhasil!",
        "Akun kamu berhasil dibuat. Silakan Login untuk melanjutkan",
        function () {
            window.location.assign("Login.php");
        }
    );
    return;
}

            if (result.status === "email_exists") {

                showError(
                    identity,
                    identityError,
                    "Email sudah terdaftar. Silakan gunakan email lain."
                );

            } else if (result.status === "phone_exists") {

                showError(
                    identity,
                    identityError,
                    "Nomor telepon sudah terdaftar. Silakan gunakan nomor lain."
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

            console.error("Register error:", error);

            showAlert(
                "Tidak dapat terhubung ke server. Silakan coba lagi."
            );

        } finally {

            registerButton.disabled = false;
            registerButton.classList.remove("loading");
        }
    });

});

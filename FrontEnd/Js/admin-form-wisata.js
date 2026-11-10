(function () {
    'use strict';

    const config = window.adminWisataConfig;
    const tokenKey = 'admin_token';
    const maxImageBytes = 10 * 1024 * 1024;
    const allowedTypes = ['image/jpeg', 'image/png'];
    const elements = {
        form: document.getElementById('destinationForm'),
        id: document.getElementById('destinationId'),
        name: document.getElementById('destinationName'),
        region: document.getElementById('destinationRegion'),
        category: document.getElementById('destinationCategory'),
        price: document.getElementById('destinationPrice'),
        maps: document.getElementById('destinationMaps'),
        image: document.getElementById('destinationImage'),
        upload: document.getElementById('uploadZone'),
        preview: document.getElementById('imagePreview'),
        emptyUpload: document.getElementById('uploadEmpty'),
        filename: document.getElementById('imageFilename'),
        imageFeedback: document.getElementById('imageFeedback'),
        message: document.getElementById('formMessage'),
        save: document.getElementById('saveDestinationButton'),
        title: document.getElementById('formTitle')
    };
    let previewUrl = '';

    function getToken() {
        try {
            const stored = window.localStorage.getItem(tokenKey)
                || window.sessionStorage.getItem(tokenKey);
            if (stored) return stored;
        } catch (storageError) {
            // The login cookie remains available when browser storage is disabled.
        }
        const cookie = document.cookie.split('; ').find(function (entry) {
            return entry.indexOf(tokenKey + '=') === 0;
        });
        return cookie ? decodeURIComponent(cookie.slice(tokenKey.length + 1)) : '';
    }

    const token = getToken();
    if (!token) {
        window.location.replace(config.loginUrl);
        return;
    }

    function clearToken() {
        try {
            window.localStorage.removeItem(tokenKey);
            window.sessionStorage.removeItem(tokenKey);
        } catch (storageError) {
            // Clear the cookie below even when browser storage is unavailable.
        }
        document.cookie = tokenKey + '=; path=/; max-age=0; SameSite=Lax';
    }

    async function request(url, options) {
        const response = await fetch(url, Object.assign({}, options || {}, {
            headers: Object.assign({
                Authorization: 'Bearer ' + token
            }, options && options.headers ? options.headers : {})
        }));
        const payload = await response.json().catch(function () {
            throw new Error('Respons server tidak dapat dibaca.');
        });
        if (response.status === 401 || response.status === 403) {
            clearToken();
            window.location.replace(config.loginUrl + '?status=401');
            throw new Error('Sesi admin berakhir. Silakan login kembali.');
        }
        if (!response.ok || payload.status === 'error') {
            throw new Error(payload.message || 'Permintaan tidak dapat diproses.');
        }
        return payload;
    }

    function showMessage(message, type) {
        elements.message.textContent = message;
        elements.message.className = 'alert alert-' + (type || 'danger');
    }

    function addOptions(select, items, placeholder) {
        items.forEach(function (item) {
            const option = document.createElement('option');
            option.value = String(item.id);
            option.textContent = item.name;
            select.appendChild(option);
        });
        if (placeholder) select.options[0].textContent = placeholder;
    }

    async function loadSelectOptions() {
        const results = await Promise.all([
            request(config.apiCategoriesUrl),
            request(config.apiRegionsUrl)
        ]);
        addOptions(elements.category, Array.isArray(results[0].data) ? results[0].data : [], 'Pilih Kategori');
        addOptions(elements.region, Array.isArray(results[1].data) ? results[1].data : [], 'Pilih wilayah');
    }

    function setImagePreview(file, filename) {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        if (file) {
            previewUrl = URL.createObjectURL(file);
            elements.preview.src = previewUrl;
            elements.preview.classList.remove('d-none');
            elements.emptyUpload.classList.add('d-none');
            elements.upload.classList.add('has-preview');
            elements.filename.textContent = filename || file.name;
        } else {
            elements.preview.removeAttribute('src');
            elements.preview.classList.add('d-none');
            elements.emptyUpload.classList.remove('d-none');
            elements.upload.classList.remove('has-preview');
            elements.filename.textContent = '';
        }
    }

    function setExistingImage(filename) {
        if (!filename) return;
        elements.preview.src = config.uploadBaseUrl + String(filename).replace(/^[/\\]+/, '').split('/').map(encodeURIComponent).join('/');
        elements.preview.classList.remove('d-none');
        elements.emptyUpload.classList.add('d-none');
        elements.upload.classList.add('has-preview');
        elements.filename.textContent = 'Foto saat ini (pilih untuk mengganti)';
    }

    function validateImage() {
        const file = elements.image.files[0];
        if (!file && !elements.id.value) {
            elements.imageFeedback.textContent = 'Foto JPG atau PNG maksimal 10MB wajib diunggah.';
            elements.imageFeedback.classList.add('is-visible');
            return false;
        }
        if (file && (!allowedTypes.includes(file.type) || file.size > maxImageBytes)) {
            elements.imageFeedback.textContent = file.size > maxImageBytes
                ? 'Ukuran foto maksimal 10MB.'
                : 'Format foto harus JPG atau PNG.';
            elements.imageFeedback.classList.add('is-visible');
            return false;
        }
        elements.imageFeedback.classList.remove('is-visible');
        return true;
    }

    async function loadDestination(id) {
        const payload = await request(config.apiDestinationsUrl + '?id=' + encodeURIComponent(id));
        const items = Array.isArray(payload.data) ? payload.data : [];
        const item = items.find(function (destination) {
            return String(destination.id) === String(id);
        });
        if (!item) throw new Error('Data wisata tidak ditemukan.');
        elements.name.value = item.name || '';
        elements.region.value = item.kabupaten && item.kabupaten.id ? String(item.kabupaten.id) : '';
        elements.category.value = item.category && item.category.id ? String(item.category.id) : '';
        elements.price.value = item.price == null ? '' : String(item.price);
        elements.maps.value = item.maps_url || '';
        setExistingImage(item.image);
    }

    elements.image.addEventListener('change', function () {
        const file = elements.image.files[0];
        if (file) setImagePreview(file);
        validateImage();
    });

    elements.upload.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            elements.image.click();
        }
    });

    ['dragenter', 'dragover'].forEach(function (eventName) {
        elements.upload.addEventListener(eventName, function (event) {
            event.preventDefault();
            elements.upload.classList.add('has-preview');
        });
    });
    ['dragleave', 'drop'].forEach(function (eventName) {
        elements.upload.addEventListener(eventName, function (event) {
            event.preventDefault();
            if (eventName === 'drop' && event.dataTransfer.files.length) {
                elements.image.files = event.dataTransfer.files;
                elements.image.dispatchEvent(new Event('change', { bubbles: true }));
            } else if (!elements.preview.src) {
                elements.upload.classList.remove('has-preview');
            }
        });
    });

    elements.form.addEventListener('submit', async function (event) {
        event.preventDefault();
        elements.form.classList.add('was-validated');
        elements.message.classList.add('d-none');

        const priceValue = elements.price.value.trim();
        const valid = elements.form.checkValidity()
            && (priceValue === '' || (Number.isFinite(Number(priceValue)) && Number(priceValue) >= 0))
            && validateImage();
        if (!valid) return;

        elements.save.disabled = true;
        elements.save.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Menyimpan...</span>';
        const formData = new FormData();
        formData.append('name', elements.name.value.trim());
        formData.append('kabupaten_id', elements.region.value);
        formData.append('category_id', elements.category.value);
        formData.append('maps_url', elements.maps.value.trim());
        if (priceValue !== '') {
            formData.append('price', priceValue);
        }
        if (elements.image.files[0]) {
            formData.append('image', elements.image.files[0]);
        }

        const destinationId = elements.id.value;
        const url = destinationId
            ? config.apiDestinationsUrl + '?id=' + encodeURIComponent(destinationId)
            : config.apiDestinationsUrl;
        const options = { method: destinationId ? 'POST' : 'POST', body: formData };

        try {
            if (destinationId) {
                const methodOverride = new FormData();
                methodOverride.append('_method', 'PUT');
                formData.forEach(function (value, key) {
                    if (key !== '_method') methodOverride.append(key, value);
                });
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { Authorization: 'Bearer ' + token },
                    body: methodOverride
                });
                if (!response.ok) {
                    const payload = await response.json().catch(function () { return {}; });
                    throw new Error(payload.message || 'Data wisata gagal diperbarui.');
                }
            } else {
                await request(url, options);
            }
            window.location.assign(config.listUrl + '?saved=1');
        } catch (error) {
            showMessage(error.message || 'Data wisata gagal disimpan.');
        } finally {
            elements.save.disabled = false;
            elements.save.innerHTML = '<i class="bi bi-floppy"></i><span>Simpan data</span>';
        }
    });

    (async function init() {
        try {
            await loadSelectOptions();
            if (elements.id.value) {
                elements.title.textContent = 'Edit Data Wisata';
                document.title = 'Edit Data Wisata | Explore Majaku Admin';
                elements.image.removeAttribute('required');
                await loadDestination(elements.id.value);
            }
        } catch (error) {
            showMessage(error.message || 'Data formulir gagal dimuat.');
            elements.save.disabled = true;
        }
    }());

    window.addEventListener('beforeunload', function () {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
    });
}());

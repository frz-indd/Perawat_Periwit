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
        nameFeedback: document.getElementById('destinationNameFeedback'),
        region: document.getElementById('destinationRegion'),
        category: document.getElementById('destinationCategory'),
        price: document.getElementById('destinationPrice'),
        description: document.getElementById('destinationDescription'),
        days: document.getElementById('destinationDays'),
        hours: document.getElementById('destinationHours'),
        maps: document.getElementById('destinationMaps'),
        longitude: document.getElementById('destinationLongitude'),
        latitude: document.getElementById('destinationLatitude'),
        image: document.getElementById('destinationImage'),
        upload: document.getElementById('uploadZone'),
        preview: document.getElementById('imagePreview'),
        emptyUpload: document.getElementById('uploadEmpty'),
        filename: document.getElementById('imageFilename'),
        imageFeedback: document.getElementById('imageFeedback'),
        message: document.getElementById('formMessage'),
        save: document.getElementById('saveDestinationButton'),
        title: document.getElementById('formTitle'),
        popup: document.getElementById('wisataPopup'),
        popupIcon: document.getElementById('wisataPopupIcon'),
        popupTitle: document.getElementById('wisataPopupTitle'),
        popupText: document.getElementById('wisataPopupText'),
        popupPrimary: document.getElementById('wisataPopupPrimary')
    };
    let previewUrl = '';
    let nameCheckSequence = 0;
    let lastCheckedName = '';
    let duplicateNameFound = false;
    if (new URLSearchParams(window.location.search).has('id')) {
        elements.form.dataset.apiMethod = 'PUT';
    }

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
            throw Object.assign(new Error(payload.message || 'Permintaan tidak dapat diproses.'), { apiPayload: payload });
        }
        return payload;
    }

    function closePopup() {
        elements.popup.classList.remove('is-visible');
        elements.popup.setAttribute('aria-hidden', 'true');
    }

    function showMessage(message, type) {
        elements.popupIcon.className = 'wisata-popup-icon';
        elements.popupIcon.textContent = '×';
        elements.popupTitle.className = 'wisata-popup-title';
        elements.popupTitle.textContent = elements.id.value ? 'Gagal Memperbarui!' : 'Gagal di Simpan !';
        elements.popupText.textContent = message || (elements.id.value
            ? 'Data wisata gagal diperbarui. Silakan coba kembali.'
            : 'Wisata gagal ditambahkan ke daftar data wisata. Silakan coba kembali.');
        elements.popupPrimary.textContent = 'Coba Lagi';
        elements.popupPrimary.onclick = closePopup;
        elements.popup.classList.add('is-visible');
        elements.popup.setAttribute('aria-hidden', 'false');
    }

    elements.popup.addEventListener('click', function (event) { if (event.target === elements.popup) closePopup(); });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closePopup(); });

    function addOptions(select, items, placeholder) {
        items.forEach(function (item) {
            const option = document.createElement('option');
            option.value = String(item.id);
            option.textContent = item.name;
            if (select === elements.category) {
                const raw = String(item.name || '').toLocaleLowerCase('id-ID');
                if (raw.includes('alam') || raw.includes('gunung')) option.textContent = 'Wisata Alam & pegunungan';
                else if (raw.includes('air') || raw.includes('curug')) option.textContent = 'Wisata Air & Curug';
                else if (raw.includes('buatan') || raw.includes('kuliner')) option.textContent = 'Wisata Buatan & Kuliner';
                else if (raw.includes('sejarah') || raw.includes('budaya')) option.textContent = 'Wisata Sejarah & Budaya';
            } else if (select === elements.region) {
                const raw = String(item.name || '').toLocaleLowerCase('id-ID');
                if (raw.includes('majalengka')) option.textContent = 'Majalengka';
                else if (raw.includes('kuningan')) option.textContent = 'Kuningan';
            }
            select.appendChild(option);
        });
        if (placeholder) select.options[0].textContent = placeholder;
    }

    async function loadSelectOptions() {
        const results = await Promise.all([
            request(config.apiCategoriesUrl),
            request(config.apiRegionsUrl)
        ]);
        addOptions(elements.category, Array.isArray(results[0].data) ? results[0].data : [], 'Pilih kategori');
        addOptions(elements.region, Array.isArray(results[1].data) ? results[1].data : [], 'Pilih wilayah');
    }

    function setImagePreview(file, filename) {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        if (file) {
            previewUrl = URL.createObjectURL(file);
            elements.preview.src = previewUrl;
            elements.preview.classList.remove('d-none');
            elements.emptyUpload.classList.remove('d-none');
            elements.upload.classList.add('has-preview');
            elements.filename.textContent = filename || file.name;
            const prompt = document.getElementById('uploadPrompt');
            if (prompt) prompt.innerHTML = 'klik Area foto untuk mengubah<br>foto atau drag &amp; drop';
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
        elements.emptyUpload.classList.remove('d-none');
        elements.upload.classList.add('has-preview');
        elements.filename.textContent = '';
        const prompt = document.getElementById('uploadPrompt');
        if (prompt) prompt.innerHTML = 'klik Area foto untuk mengubah<br>foto atau drag &amp; drop';
    }

    function setSelectError(select, message) {
        const field = select.closest('.admin-field');
        if (!field) return;
        const feedback = field.querySelector('.invalid-feedback');
        const wrapper = select.closest('.admin-custom-select');
        const trigger = wrapper && wrapper.querySelector('.admin-custom-select__trigger');
        const hasError = !select.value;
        if (feedback) {
            feedback.textContent = message;
            feedback.classList.toggle('is-visible', hasError);
        }
        if (trigger) {
            trigger.classList.toggle('is-invalid', hasError);
            trigger.setAttribute('aria-invalid', hasError ? 'true' : 'false');
        }
        select.setAttribute('aria-invalid', hasError ? 'true' : 'false');
    }

    function setCoordinateError(input, message) {
        const field = input.closest('.admin-field');
        const feedback = field && field.querySelector('.invalid-feedback');
        const hasError = Boolean(message);
        input.classList.toggle('is-invalid', hasError);
        input.setAttribute('aria-invalid', hasError ? 'true' : 'false');
        if (feedback) {
            feedback.textContent = message || '';
            feedback.classList.toggle('is-visible', hasError);
        }
    }

    function isValidGoogleMapsUrl(value) {
        if (!value) return false;
        try {
            const parsed = new URL(value);
            if (!['http:', 'https:'].includes(parsed.protocol)) return false;
            const host = parsed.hostname.toLowerCase().replace(/^www\\./, '');
            if (host === 'maps.app.goo.gl' || host === 'goo.gl') return true;
            if (host === 'maps.google.com') return true;
            return (host === 'google.com' || host.endsWith('.google.com'))
                && parsed.pathname.toLowerCase().includes('/maps');
        } catch (error) {
            return false;
        }
    }

    function validateLocationFields() {
        const latitude = elements.latitude.value.trim();
        const longitude = elements.longitude.value.trim();
        const maps = elements.maps.value.trim();
        let valid = true;

        if (!latitude) {
            setCoordinateError(elements.latitude, 'Latitude wajib diisi');
            valid = false;
        } else if (!Number.isFinite(Number(latitude)) || Number(latitude) < -90 || Number(latitude) > 90) {
            setCoordinateError(elements.latitude, 'Latitude harus antara -90 dan 90.');
            valid = false;
        } else {
            setCoordinateError(elements.latitude, '');
        }

        if (!longitude) {
            setCoordinateError(elements.longitude, 'Longitude wajib diisi');
            valid = false;
        } else if (!Number.isFinite(Number(longitude)) || Number(longitude) < -180 || Number(longitude) > 180) {
            setCoordinateError(elements.longitude, 'Longitude harus antara -180 dan 180.');
            valid = false;
        } else {
            setCoordinateError(elements.longitude, '');
        }

        const mapsField = elements.maps.closest('.admin-field');
        const mapsFeedback = mapsField && mapsField.querySelector('.invalid-feedback');
        let mapsError = '';
        if (!maps) mapsError = 'Tautan Google maps wajib diisi.';
        else if (!isValidGoogleMapsUrl(maps)) mapsError = 'Format tautan Google Maps tidak valid. Periksa kembali tautan anda.';
        elements.maps.classList.toggle('is-invalid', Boolean(mapsError));
        elements.maps.setAttribute('aria-invalid', mapsError ? 'true' : 'false');
        if (mapsFeedback) {
            mapsFeedback.textContent = mapsError;
            mapsFeedback.classList.toggle('is-visible', Boolean(mapsError));
        }
        if (mapsError) valid = false;
        return valid;
    }

    [elements.latitude, elements.longitude, elements.maps].forEach(function (input) {
        input.addEventListener('input', function () {
            if (elements.form.classList.contains('was-validated')) validateLocationFields();
        });
        input.addEventListener('blur', function () {
            if (elements.form.classList.contains('was-validated')) validateLocationFields();
        });
    });

    function showNameError(message) {
        if (elements.nameFeedback) {
            elements.nameFeedback.textContent = message || 'Nama wisata wajib diisi.';
            elements.nameFeedback.classList.add('is-visible');
        }
    }

    function clearNameError() {
        duplicateNameFound = false;
        lastCheckedName = '';
        if (elements.nameFeedback) elements.nameFeedback.classList.remove('is-visible');
    }

    async function checkDuplicateName() {
        const name = elements.name.value.trim();
        const normalizedName = name.toLocaleLowerCase('id-ID');
        if (!name) {
            clearNameError();
            return false;
        }
        if (normalizedName === lastCheckedName) return duplicateNameFound;

        const sequence = ++nameCheckSequence;
        const params = new URLSearchParams({ check_name: name });
        if (elements.id.value) params.set('exclude_id', elements.id.value);
        try {
            const result = await request(config.apiDestinationsUrl + '?' + params.toString());
            if (sequence !== nameCheckSequence) return false;
            lastCheckedName = normalizedName;
            duplicateNameFound = result.exists === true;
            if (duplicateNameFound) showNameError('Nama destinasi wisata sudah terdaftar, gunakan nama lain.');
            else if (elements.nameFeedback) elements.nameFeedback.classList.remove('is-visible');
            return duplicateNameFound;
        } catch (error) {
            // Pemeriksaan duplikat di server saat POST/PUT tetap menjadi perlindungan terakhir.
            return false;
        }
    }

    elements.name.addEventListener('input', function () {
        ++nameCheckSequence;
        clearNameError();
    });
    elements.name.addEventListener('blur', checkDuplicateName);

    function validateRequiredSelects() {
        const regionValid = Boolean(String(elements.region.value || '').trim());
        const categoryValid = Boolean(String(elements.category.value || '').trim());
        setSelectError(elements.region, 'Silahkan pilih wilayah terlebih dahulu.');
        setSelectError(elements.category, 'Silahkan pilih kategori wisata terlebih dahulu.');
        return regionValid && categoryValid;
    }

    elements.region.addEventListener('change', function () {
        setSelectError(elements.region, 'Silahkan pilih wilayah terlebih dahulu.');
    });
    elements.category.addEventListener('change', function () {
        setSelectError(elements.category, 'Silahkan pilih kategori wisata terlebih dahulu.');
    });

    function setImageFeedback(message) {
        // Satu elemen feedback hanya boleh menampilkan satu pesan dalam satu waktu.
        elements.imageFeedback.classList.remove('is-visible');
        elements.imageFeedback.textContent = message || '';
        if (message) elements.imageFeedback.classList.add('is-visible');
    }

    function validateImage() {
        const file = elements.image.files[0];
        const hasExistingImage = Boolean(elements.preview.getAttribute('src'));

        // Validasi dijalankan berurutan: wajib unggah -> ukuran -> format.
        // Begitu satu kondisi gagal, pesan berikutnya tidak akan ditampilkan.
        if (!file && !hasExistingImage) {
            setImageFeedback('Foto destinasi wisata wajib diunggah');
            return false;
        }

        if (file && file.size > maxImageBytes) {
            setImageFeedback('Ukuran foto maksimal 10 MB');
            return false;
        }

        if (file && !allowedTypes.includes(file.type)) {
            setImageFeedback('Format foto tidak didukung. Gunakan JPG, JPEG atau PNG.');
            return false;
        }

        setImageFeedback('');
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

        // API dapat mengirim wilayah/kategori sebagai ID, objek, atau nama teks.
        // Cocokkan juga nama dengan label option agar select tetap terpilih.
        function selectRelatedValue(select, candidates) {
            for (const candidate of candidates) {
                if (candidate === undefined || candidate === null || candidate === '') continue;
                const value = typeof candidate === 'object'
                    ? (candidate.id ?? candidate.value ?? candidate.name ?? '')
                    : candidate;
                if (value === '') continue;
                const exactValue = Array.from(select.options).find(function (option) {
                    return String(option.value) === String(value);
                });
                if (exactValue) return exactValue.value;
                const normalized = String(value).trim().toLocaleLowerCase('id');
                const matchingLabel = Array.from(select.options).find(function (option) {
                    if (option.value === '') return false;
                    const label = option.textContent.trim().toLocaleLowerCase('id');
                    const optionValue = String(option.value).trim().toLocaleLowerCase('id');
                    return label === normalized || optionValue === normalized
                        || normalized.includes(label) || label.includes(normalized)
                        || normalized.includes(optionValue) || optionValue.includes(normalized);
                });
                if (matchingLabel) return matchingLabel.value;
            }
            return '';
        }

        elements.region.value = selectRelatedValue(elements.region, [
            item.kabupaten_id, item.region_id, item.region_detail, item.region, item.kabupaten,
            item.nama_wilayah, item.wilayah, item.kabupaten_nama
        ]);
        elements.category.value = selectRelatedValue(elements.category, [
            item.category_id, item.category_detail, item.category, item.category_name, item.nama_kategori
        ]);
        elements.price.value = item.price == null ? '' : String(item.price);
        elements.description.value = item.description || item.deskripsi || '';
        elements.days.value = item.operating_days || item.hari_operasional || '';
        elements.hours.value = item.operating_hours || item.jam_operasional || ((item.open_time && item.close_time) ? item.open_time + ' - ' + item.close_time : '');
        elements.maps.value = item.maps_url || '';
        elements.longitude.value = item.longitude == null ? '' : String(item.longitude);
        elements.latitude.value = item.latitude == null ? '' : String(item.latitude);
        setExistingImage(item.image);
    }

    elements.image.addEventListener('change', function () {
        const file = elements.image.files[0];
        const valid = validateImage();
        if (file && valid) setImagePreview(file);
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
        clearNameError();

        // Jalankan seluruh validasi form seperti sebelumnya. Validasi foto
        // tetap memakai satu area pesan dan hanya menampilkan satu kondisi.
        const requiredSelectsValid = validateRequiredSelects();
        const priceValue = elements.price.value.trim();
        const locationFieldsValid = validateLocationFields();
        const imageValid = validateImage();
        const valid = requiredSelectsValid && elements.form.checkValidity()
            && (priceValue === '' || (Number.isFinite(Number(priceValue)) && Number(priceValue) >= 0))
            && imageValid
            && locationFieldsValid;
        if (!valid) return;

        if (await checkDuplicateName()) {
            elements.name.focus();
            return;
        }

        elements.save.disabled = true;
        elements.save.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Menyimpan...</span>';
        const formData = new FormData();
        formData.append('name', elements.name.value.trim());
        formData.append('domisili', elements.region.value);
        formData.append('category_id', elements.category.value);
        formData.append('description', elements.description.value.trim());
        formData.append('operating_days', elements.days.value.trim());
        formData.append('operating_hours', elements.hours.value.trim());
        formData.append('maps_url', elements.maps.value.trim());
        formData.append('longitude', elements.longitude.value.trim());
        formData.append('latitude', elements.latitude.value.trim());
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
                // Backend menerima PUT melalui method override agar unggahan multipart tetap terbaca PHP.
                const methodOverride = new FormData();
                methodOverride.append('_method', 'PUT');
                formData.forEach(function (value, key) {
                    methodOverride.append(key, value);
                });
                await request(url, { method: 'POST', body: methodOverride });
            } else {
                await request(url, options);
            }
            window.location.assign(config.listUrl + (destinationId ? '?saved=updated' : '?saved=created'));
        } catch (error) {
            if (error.apiPayload && (error.apiPayload.field === 'name' || /nama destinasi wisata sudah terdaftar/i.test(error.message))) {
                duplicateNameFound = true;
                lastCheckedName = elements.name.value.trim().toLocaleLowerCase('id-ID');
                showNameError('Nama destinasi wisata sudah terdaftar, gunakan nama lain.');
                elements.name.focus();
            } else {
                showMessage(error.message || 'Data wisata gagal disimpan.');
            }
        } finally {
            elements.save.disabled = false;
            elements.save.innerHTML = '<i class="bi bi-floppy"></i><span>' + (destinationId ? 'Simpan perubahan' : 'Simpan data') + '</span>';
        }
    });

    (async function init() {
        try {
            await loadSelectOptions();
            if (elements.id.value) {
                elements.title.textContent = 'Edit Data Wisata';
                elements.save.querySelector('span').textContent = 'Simpan perubahan';
                document.title = 'Edit Data Wisata | Explore Majaku Admin';
                elements.image.removeAttribute('required');
                await loadDestination(elements.id.value);
            } else {
                elements.image.setAttribute('required', 'required');
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

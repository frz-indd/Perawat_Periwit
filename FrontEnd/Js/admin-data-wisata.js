(function () {
    'use strict';

    const config = window.adminWisataConfig;
    const tokenKey = 'admin_token';
    const pageSize = 5;
    const elements = {
        rows: document.getElementById('destinationRows'),
        summary: document.getElementById('resultSummary'),
        message: document.getElementById('pageMessage'),
        search: document.getElementById('searchInput'),
        searchButton: document.getElementById('searchButton'),
        region: document.getElementById('regionFilter'),
        category: document.getElementById('categoryFilter'),
        pagination: document.getElementById('paginationLinks'),
        deleteButton: document.getElementById('confirmDeleteButton'),
        sidebar: document.getElementById('adminSidebar'),
        backdrop: document.getElementById('sidebarBackdrop')
    };
    const state = {
        page: 1,
        pageSize: pageSize,
        count: 0,
        total: 0,
        deleteId: null,
        deleteModal: window.bootstrap
            ? new window.bootstrap.Modal(document.getElementById('deleteConfirmModal'))
            : null
    };

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

    function clearToken() {
        try {
            window.localStorage.removeItem(tokenKey);
            window.sessionStorage.removeItem(tokenKey);
        } catch (storageError) {
            // Clear the cookie below even when browser storage is unavailable.
        }
        document.cookie = tokenKey + '=; path=/; max-age=0; SameSite=Lax';
    }

    function showMessage(message, type) {
        elements.message.textContent = message;
        elements.message.className = 'alert admin-alert alert-' + (type || 'danger');
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[character];
        });
    }

    function imageUrl(filename) {
        if (!filename) return '';
        const safeFilename = String(filename).split(/[\\/]/).pop();
        return config.uploadBaseUrl + encodeURIComponent(safeFilename);
    }

    function formatPrice(price) {
        if (price === null || price === undefined || price === '') return '-';
        const amount = Number(price);
        return Number.isFinite(amount)
            ? new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(amount)
            : '-';
    }

    function renderRows(destinations) {
        if (!destinations.length) {
            elements.rows.innerHTML = '<tr><td class="table-state" colspan="9">Data wisata tidak ditemukan.</td></tr>';
            return;
        }

        elements.rows.innerHTML = destinations.map(function (item, index) {
            const rowNumber = (state.page - 1) * state.pageSize + index + 1;
            const photo = imageUrl(item.image);
            const mapsUrl = item.maps_url && /^https?:\/\//i.test(item.maps_url)
                ? item.maps_url
                : '';
            const imageCell = photo
                ? '<img class="destination-photo" src="' + escapeHtml(photo) + '" alt="Foto ' + escapeHtml(item.name) + '" loading="lazy">'
                : '<span class="destination-photo d-grid place-items-center" aria-label="Foto tidak tersedia"><i class="bi bi-image"></i></span>';
            const locationCell = mapsUrl
                ? '<a class="destination-location" href="' + escapeHtml(mapsUrl) + '" target="_blank" rel="noopener noreferrer" aria-label="Buka lokasi ' + escapeHtml(item.name) + '"><svg viewBox="0 0 18 22" aria-hidden="true" focusable="false"><path class="location-pin__body" d="M9 0C4.6 0 1 3.5 1 7.8c0 5.2 8 13.2 8 13.2s8-8 8-13.2C17 3.5 13.4 0 9 0Z"/><circle class="location-pin__center" cx="9" cy="7.5" r="2.6"/><path class="location-pin__ground" d="M2.4 19.2c0-1.3 3-2.4 6.6-2.4s6.6 1.1 6.6 2.4-3 2.4-6.6 2.4-6.6-1.1-6.6-2.4Z"/></svg></a>'
                : '<span class="text-secondary" aria-label="Lokasi belum tersedia">-</span>';
            const rating = Number(item.rating);
            const ratingCell = item.rating !== null && item.rating !== undefined && item.rating !== '' && Number.isFinite(rating)
                ? '<i class="bi bi-star-fill destination-rating-star" aria-hidden="true"></i> ' + rating.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 })
                : '-';

            return '<tr>'
                + '<td>' + rowNumber + '</td>'
                + '<td>' + imageCell + '</td>'
                + '<td>' + escapeHtml(item.name || '-') + '</td>'
                + '<td>' + escapeHtml(item.kabupaten && item.kabupaten.name || item.region || '-') + '</td>'
                + '<td>' + escapeHtml(item.category && item.category.name || '-') + '</td>'
                + '<td>' + formatPrice(item.price) + '</td>'
                + '<td>' + ratingCell + '</td>'
                + '<td>' + locationCell + '</td>'
                + '<td><a class="destination-edit" href="' + escapeHtml(config.formUrl) + '?id=' + encodeURIComponent(item.id) + '" aria-label="Edit ' + escapeHtml(item.name) + '"><i class="bi bi-pencil-fill"></i></a>'
                + '<button class="destination-delete" type="button" data-delete-id="' + escapeHtml(item.id) + '" aria-label="Hapus ' + escapeHtml(item.name) + '"><i class="bi bi-trash-fill"></i></button></td>'
                + '</tr>';
        }).join('');
    }

    function updatePagination() {
        const totalPages = Math.ceil(state.total / state.pageSize);
        const pages = [];
        for (let page = 1; page <= totalPages; page += 1) {
            if (page === 1 || page === totalPages || Math.abs(page - state.page) <= 1) {
                pages.push(page);
            }
        }

        const links = [];
        let previousPage = 0;
        pages.forEach(function (page) {
            if (previousPage && page - previousPage > 1) {
                links.push('<li class="page-item page-item-ellipsis"><span class="page-link">...</span></li>');
            }
            const isCurrent = page === state.page;
            links.push('<li class="page-item' + (isCurrent ? ' active' : '') + '"' + (isCurrent ? ' aria-current="page"' : '') + '>'
                + '<button class="page-link" type="button" data-page="' + page + '" aria-label="Halaman ' + page + '"' + (isCurrent ? ' aria-current="page"' : '') + '>' + page + '</button>'
                + '</li>');
            previousPage = page;
        });

        const previousDisabled = state.page <= 1;
        const nextDisabled = state.page >= totalPages;
        elements.pagination.innerHTML = '<li class="page-item' + (previousDisabled ? ' disabled' : '') + '">'
            + '<button class="page-link" type="button" data-page="' + (state.page - 1) + '" aria-label="Halaman sebelumnya"' + (previousDisabled ? ' disabled' : '') + '><i class="bi bi-chevron-left"></i></button>'
            + '</li>' + links.join('')
            + '<li class="page-item' + (nextDisabled ? ' disabled' : '') + '">'
            + '<button class="page-link" type="button" data-page="' + (state.page + 1) + '" aria-label="Halaman berikutnya"' + (nextDisabled ? ' disabled' : '') + '><i class="bi bi-chevron-right"></i></button>'
            + '</li>';

        const first = state.count ? ((state.page - 1) * state.pageSize) + 1 : 0;
        const last = ((state.page - 1) * state.pageSize) + state.count;
        elements.summary.textContent = 'Menampilkan ' + first + '-' + last + ' dari ' + state.total + ' wisata';
    }

    async function loadDestinations() {
        elements.rows.innerHTML = '<tr><td class="table-state" colspan="9"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memuat data wisata...</td></tr>';
        elements.message.classList.add('d-none');
        const params = new URLSearchParams({
            page: String(state.page),
            per_page: String(pageSize),
            search: elements.search.value.trim(),
            kabupaten: elements.region.value,
            category: elements.category.value
        });

        try {
            const payload = await request(config.apiDestinationsUrl + '?' + params.toString());
            const destinations = Array.isArray(payload.data) ? payload.data : [];
            state.count = destinations.length;
            state.total = Number(payload.total);
            state.pageSize = Number(payload.per_page) || pageSize;
            if (!Number.isFinite(state.total)) {
                state.total = destinations.length;
            }
            renderRows(destinations);
            updatePagination();
        } catch (error) {
            if (error.message !== 'Sesi admin berakhir. Silakan login kembali.') {
                elements.rows.innerHTML = '<tr><td class="table-state" colspan="9">Data wisata gagal dimuat.</td></tr>';
                showMessage(error.message || 'Data wisata gagal dimuat.');
                elements.summary.textContent = '';
            }
        }
    }

    function fillOptions(select, items, placeholder) {
        items.forEach(function (item) {
            const option = document.createElement('option');
            option.value = String(item.id);
            option.textContent = item.name;
            select.appendChild(option);
        });
        if (placeholder) select.options[0].textContent = placeholder;
    }

    async function loadFilters() {
        const results = await Promise.all([
            request(config.apiCategoriesUrl),
            request(config.apiRegionsUrl)
        ]);
        fillOptions(elements.category, Array.isArray(results[0].data) ? results[0].data : [], 'Semua Kategori');
        fillOptions(elements.region, Array.isArray(results[1].data) ? results[1].data : [], 'Semua wilayah');
    }

    elements.searchButton.addEventListener('click', function () {
        state.page = 1;
        loadDestinations();
    });
    elements.search.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            state.page = 1;
            loadDestinations();
        }
    });
    elements.region.addEventListener('change', function () {
        state.page = 1;
        loadDestinations();
    });
    elements.category.addEventListener('change', function () {
        state.page = 1;
        loadDestinations();
    });
    elements.pagination.addEventListener('click', function (event) {
        const button = event.target.closest('button[data-page]');
        if (!button || button.disabled) return;
        const page = Number(button.getAttribute('data-page'));
        if (Number.isInteger(page) && page > 0 && page !== state.page) {
            state.page = page;
            loadDestinations();
        }
    });
    elements.rows.addEventListener('click', function (event) {
        const button = event.target.closest('[data-delete-id]');
        if (!button) return;
        state.deleteId = button.getAttribute('data-delete-id');
        if (state.deleteModal) {
            state.deleteModal.show();
        } else if (window.confirm('Hapus data wisata ini?')) {
            deleteDestination();
        }
    });
    elements.deleteButton.addEventListener('click', deleteDestination);

    async function deleteDestination() {
        if (!state.deleteId) return;
        elements.deleteButton.disabled = true;
        try {
            await request(config.apiDestinationsUrl + '?id=' + encodeURIComponent(state.deleteId), {
                method: 'DELETE'
            });
            if (state.deleteModal) state.deleteModal.hide();
            if (state.count === 1 && state.page > 1) state.page -= 1;
            await loadDestinations();
            showMessage('Destinasi wisata berhasil dihapus.', 'success');
        } catch (error) {
            showMessage(error.message || 'Data wisata gagal dihapus.');
        } finally {
            state.deleteId = null;
            elements.deleteButton.disabled = false;
        }
    }

    document.getElementById('logoutButton').addEventListener('click', logout);
    document.getElementById('mobileLogoutButton').addEventListener('click', logout);
    async function logout() {
        try {
            if (token) {
                await fetch(config.apiLogoutUrl, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
                });
            }
        } catch (error) {
            // Tetap bersihkan token lokal; token server akan kedaluwarsa sesuai exp.
        } finally {
            clearToken();
            window.location.replace(config.loginUrl);
        }
    }

    document.getElementById('sidebarCollapse').addEventListener('click', function () {
        document.body.classList.toggle('sidebar-collapsed');
    });
    document.getElementById('mobileMenuButton').addEventListener('click', function () {
        document.body.classList.toggle('mobile-sidebar-open');
    });
    elements.backdrop.addEventListener('click', function () {
        document.body.classList.remove('mobile-sidebar-open');
    });

    loadFilters()
        .then(loadDestinations)
        .catch(function (error) {
            elements.rows.innerHTML = '<tr><td class="table-state" colspan="9">Filter data wisata gagal dimuat.</td></tr>';
            showMessage(error.message || 'Filter data wisata gagal dimuat.');
        });
}());

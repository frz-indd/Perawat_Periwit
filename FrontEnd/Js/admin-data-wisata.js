(function () {
    'use strict';

    const config = window.adminWisataConfig;
    const tokenKey = 'admin_token';
    const pageSize = 5;
    const elements = {
        rows: document.getElementById('destinationRows'),
        statePanel: document.getElementById('destinationStatePanel'),
        tableWrap: document.getElementById('destinationTableWrap'),
        tableFooter: document.getElementById('destinationTableFooter'),
        summary: document.getElementById('resultSummary'),
        message: document.getElementById('pageMessage'),
        search: document.getElementById('searchInput'),
        searchButton: document.getElementById('searchButton'),
        region: document.getElementById('regionFilter'),
        category: document.getElementById('categoryFilter'),
        pagination: document.getElementById('paginationLinks'),
        sidebar: document.getElementById('adminSidebar'),
        backdrop: document.getElementById('sidebarBackdrop'),
        popup: document.getElementById('wisataPopup'),
        popupIcon: document.getElementById('wisataPopupIcon'),
        popupTitle: document.getElementById('wisataPopupTitle'),
        popupText: document.getElementById('wisataPopupText'),
        popupActions: document.getElementById('wisataPopupActions'),
        popupPrimary: document.getElementById('wisataPopupPrimary')
    };
    const state = {
        page: 1,
        pageSize: pageSize,
        count: 0,
        total: 0,
        deleteId: null
    };

    function closePopup() {
        elements.popup.classList.remove('is-visible');
        elements.popup.setAttribute('aria-hidden', 'true');
    }

    function showPopup(type, title, message, primaryLabel, onPrimary, confirmMode) {
        elements.popupIcon.className = 'wisata-popup-icon' + (type === 'success' ? ' is-success' : (confirmMode ? ' is-confirm' : ''));
        elements.popupIcon.innerHTML = type === 'success' ? '<i class="bi bi-check-lg"></i>' : (confirmMode ? '<i class="bi bi-trash-fill"></i>' : '×');
        elements.popupTitle.className = 'wisata-popup-title' + (type === 'success' || confirmMode ? ' is-' + (confirmMode ? 'confirm' : 'success') : '');
        elements.popupTitle.textContent = title;
        elements.popupText.textContent = message;
        elements.popupActions.innerHTML = '';
        if (confirmMode) {
            const yes = document.createElement('button');
            yes.type = 'button'; yes.className = 'wisata-popup-button'; yes.textContent = 'Ya, Hapus';
            yes.addEventListener('click', onPrimary);
            const cancel = document.createElement('button');
            cancel.type = 'button'; cancel.className = 'wisata-popup-button is-outline'; cancel.textContent = 'Batal';
            cancel.addEventListener('click', closePopup);
            elements.popupActions.append(yes, cancel);
        } else {
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'wisata-popup-button'; button.textContent = primaryLabel || 'Coba Lagi';
            button.addEventListener('click', function () { closePopup(); if (onPrimary) onPrimary(); });
            elements.popupActions.appendChild(button);
        }
        elements.popup.classList.add('is-visible');
        elements.popup.setAttribute('aria-hidden', 'false');
    }

    elements.popup.addEventListener('click', function (event) { if (event.target === elements.popup) closePopup(); });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closePopup(); });

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
        const cleanPath = String(filename).replace(/^\/+/, '');
        return config.uploadBaseUrl + cleanPath.split('/').map(encodeURIComponent).join('/');
    }

    function categoryLabel(value) {
        const raw = String(value == null ? '' : value).toLocaleLowerCase('id-ID');
        if (raw.includes('alam') || raw.includes('gunung')) return 'Wisata Alam & pegunungan';
        if (raw.includes('air') || raw.includes('curug')) return 'Wisata Air & Curug';
        if (raw.includes('buatan') || raw.includes('kuliner')) return 'Wisata Buatan & Kuliner';
        if (raw.includes('sejarah') || raw.includes('budaya')) return 'Wisata Sejarah & Budaya';
        return String(value == null || value === '' ? '-' : value);
    }

    function regionLabel(value) {
        const raw = String(value == null ? '' : value).toLocaleLowerCase('id-ID');
        if (raw.includes('majalengka')) return 'Majalengka';
        if (raw.includes('kuningan')) return 'Kuningan';
        return String(value == null || value === '' ? '-' : value);
    }

    function formatPrice(price) {
        if (price === null || price === undefined || price === '') return '-';
        const amount = Number(price);
        return Number.isFinite(amount)
            ? new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(amount)
            : '-';
    }

    function showTable() {
        elements.statePanel.classList.add('d-none');
        elements.tableWrap.classList.remove('d-none');
        elements.tableFooter.classList.remove('d-none');
    }

    function showState(kind, message) {
        elements.tableWrap.classList.add('d-none');
        elements.tableFooter.classList.add('d-none');
        elements.statePanel.classList.remove('d-none');
        const states = {
            empty: { icon: 'bi-geo-alt', title: 'Belum ada data destinasi wisata', text: 'Silakan tambahkan destinasi wisata untuk mulai mengelola data.', action: '<a class="destination-state-button" href="' + escapeHtml(config.formUrl) + '">Tambah data wisata</a>' },
            error: { icon: 'bi-x-circle', title: 'Gagal Memuat data wisata', text: message || 'Maaf, Data Wisata gagal di muat. Silahkan coba kembali.', action: '<button class="destination-state-button" type="button" data-state-action="retry">Coba lagi</button>' },
            notFound: { icon: 'custom-search-empty', title: 'Data Wisata Tidak Di Temukan', text: 'Maaf, tidak ada data destinasi wisata yang sesuai dengan kata kunci pencarianmu', action: '<button class="destination-state-button" type="button" data-state-action="reset">Coba cari dengan kata lain</button>' }
        };
        const item = states[kind];
        const emptySearchSvg = '<svg class="destination-empty-search" width="118" height="118" viewBox="0 0 118 118" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M76.2093 68.8333H72.3251L70.9484 67.5058C75.9337 61.7242 78.6735 54.3425 78.6676 46.7083C78.6676 38.2325 75.3006 30.1037 69.3072 24.1104C63.3139 18.117 55.1851 14.75 46.7093 14.75C29.8943 14.75 16.1276 27.73 14.8984 44.25H24.8301C26.0593 33.1875 35.3026 24.5833 46.7093 24.5833C58.9518 24.5833 68.8343 34.4658 68.8343 46.7083C68.8343 58.9508 58.9518 68.8333 46.7093 68.8333C45.8734 68.8333 45.0868 68.6858 44.2509 68.5875V78.5192C45.0868 78.6175 45.8734 78.6667 46.7093 78.6667C54.6251 78.6667 61.9018 75.7658 67.5068 70.9475L68.8343 72.3242V76.2083L93.4176 100.743L100.743 93.4167L76.2093 68.8333Z" fill="#166C5C"/><path d="M31.8104 53.1982L19.6663 65.3424L7.52208 53.1982L4.03125 56.6891L16.1754 68.8332L4.03125 80.9774L7.52208 84.4682L19.6663 72.3241L31.8104 84.4682L35.3012 80.9774L23.1571 68.8332L35.3012 56.6891L31.8104 53.1982Z" fill="#166C5C"/></svg>'; const stateIcon = item.icon === 'custom-search-empty' ? emptySearchSvg : '<i class="bi ' + item.icon + '" aria-hidden="true"></i>'; elements.statePanel.innerHTML = '<div class="destination-state-content destination-state-' + kind + '">' + stateIcon + '<h2>' + item.title + '</h2><p>' + escapeHtml(item.text) + '</p>' + item.action + '</div>';
    }

    function renderRows(destinations) {
        if (!destinations.length) {
            const hasFilter = Boolean(elements.search.value.trim() || elements.region.value || elements.category.value);
            showState(hasFilter ? 'notFound' : 'empty');
            return;
        }
        showTable();

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
                + '<td>' + escapeHtml(regionLabel(item.region || item.kabupaten || '-')) + '</td>'
                + '<td>' + escapeHtml(categoryLabel(typeof item.category === 'string' ? item.category : (item.category && item.category.name) || '-')) + '</td>'
                + '<td class="destination-description">' + escapeHtml(item.description || '-') + '</td>'
                + '<td>' + formatPrice(item.price) + '</td>'
                + '<td>' + escapeHtml(item.operating_days || '-') + '</td>'
                + '<td>' + escapeHtml(item.operating_hours || ((item.open_time && item.close_time) ? item.open_time + ' - ' + item.close_time : '-')) + '</td>'
                + '<td>' + ratingCell + '</td>'
                + '<td>' + locationCell + '</td>'
                + '<td><a class="destination-edit" href="' + escapeHtml(config.formUrl) + '?id=' + encodeURIComponent(item.id) + '" aria-label="Edit ' + escapeHtml(item.name) + '"><i class="bi bi-pencil"></i></a>'
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
        showTable();
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
            state.total = Number(payload.total ?? payload.total_data ?? 0);
            state.pageSize = Number(payload.per_page) || pageSize;
            if (!Number.isFinite(state.total)) {
                state.total = destinations.length;
            }
            renderRows(destinations);
            updatePagination();
        } catch (error) {
            if (error.message !== 'Sesi admin berakhir. Silakan login kembali.') {
                showState('error', 'Maaf, Data Wisata gagal di muat. Silahkan coba kembali.');
                elements.summary.textContent = '';
            }
        }
    }

    function fillOptions(select, items, placeholder) {
        items.forEach(function (item) {
            const option = document.createElement('option');
            option.value = String(item.id);
            if (select === elements.category) option.textContent = categoryLabel(item.name);
            else if (select === elements.region) option.textContent = regionLabel(item.name);
            else option.textContent = item.name;
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

    elements.statePanel.addEventListener('click', function (event) {
        const action = event.target.closest('[data-state-action]');
        if (!action) return;
        if (action.dataset.stateAction === 'retry') { loadDestinations(); }
        if (action.dataset.stateAction === 'reset') {
            elements.search.value = ''; elements.region.value = ''; elements.category.value = '';
            state.page = 1; loadDestinations();
        }
    });

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
        const row = button.closest('tr');
        const destinationName = row && row.children[2] ? row.children[2].textContent.trim() : 'destinasi wisata';
        showPopup('confirm', 'Apakah Anda yakin ingin menghapus destinasi wisata ' + destinationName + '?', 'Tindakan ini tidak bisa dibatalkan', 'Ya, Hapus', deleteDestination, true);
    });

    async function deleteDestination() {
        if (!state.deleteId) return;
        const deleteActionButton = elements.popupActions.querySelector('.wisata-popup-button');
        if (deleteActionButton) deleteActionButton.disabled = true;
        try {
            await request(config.apiDestinationsUrl + '?id=' + encodeURIComponent(state.deleteId), {
                method: 'DELETE'
            });
            closePopup();
            if (state.count === 1 && state.page > 1) state.page -= 1;
            await loadDestinations();
            state.deleteId = null;
            showMessage('Destinasi wisata berhasil dihapus.', 'success');
        } catch (error) {
            showPopup('error', 'Gagal di Hapus !', 'Wisata ' + (document.querySelector('[data-delete-id="' + CSS.escape(String(state.deleteId)) + '"]')?.closest('tr')?.children[2]?.textContent || '') + ' gagal di hapus dari data wisata', 'Coba Lagi', deleteDestination, false);
        } finally {
            if (deleteActionButton) deleteActionButton.disabled = false;
        }
    }

    // ── Logout dengan modal konfirmasi ──────────────────────────────────────
    const logoutModal         = document.getElementById('logoutModal');
    const logoutConfirmButton = document.getElementById('logoutConfirmButton');
    const logoutCancelButton  = document.getElementById('logoutCancelButton');

    function openLogoutModal() {
        document.body.classList.remove('mobile-sidebar-open');
        logoutModal.hidden = false;
        document.body.classList.add('logout-modal-open');
        setTimeout(function () {
            logoutModal.classList.add('is-visible');
            logoutCancelButton.focus();
        }, 10);
    }

    function closeLogoutModal() {
        logoutModal.classList.remove('is-visible');
        document.body.classList.remove('logout-modal-open');
        setTimeout(function () { logoutModal.hidden = true; }, 220);
    }

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

    document.getElementById('logoutButton').addEventListener('click', openLogoutModal);
    document.getElementById('mobileLogoutButton').addEventListener('click', openLogoutModal);
    logoutCancelButton.addEventListener('click', closeLogoutModal);
    document.getElementById('logoutModalBackdrop').addEventListener('click', closeLogoutModal);
    logoutConfirmButton.addEventListener('click', function () {
        logoutConfirmButton.disabled = true;
        logoutCancelButton.disabled = true;
        logoutConfirmButton.textContent = 'Memproses...';
        logout();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !logoutModal.hidden && !logoutConfirmButton.disabled) {
            closeLogoutModal();
        }
    });
    // ────────────────────────────────────────────────────────────────────────

    document.getElementById('sidebarCollapse').addEventListener('click', function () {
        document.body.classList.toggle('sidebar-collapsed');
    });
    document.getElementById('mobileMenuButton').addEventListener('click', function () {
        document.body.classList.toggle('mobile-sidebar-open');
    });
    elements.backdrop.addEventListener('click', function () {
        document.body.classList.remove('mobile-sidebar-open');
    });

    const resultStatus = new URLSearchParams(window.location.search).get('saved');
    if (resultStatus === 'created') {
        showPopup('success', 'Berhasil di simpan!', 'Wisata berhasil ditambahkan ke daftar data wisata', 'OK', function () {}, false);
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (resultStatus === 'updated') {
        showPopup('success', 'Perubahan Berhasil di simpan!', 'Data wisata berhasil diperbarui', 'OK', function () {}, false);
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    loadFilters()
        .then(loadDestinations)
        .catch(function (error) {
            showState('error', 'Filter data wisata gagal dimuat. Silakan coba kembali.');
        });
}());

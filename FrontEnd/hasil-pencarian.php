<?php
$keyword = trim((string) ($_GET["keyword"] ?? ""));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian | Explore Majaku</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="CSS/beranda.css?v=20261013-1">
    <link rel="stylesheet" href="CSS/search.css">
</head>
<body>
    <header class="navbar">
        <div class="navbar-container">
            <a href="beranda.php" class="navbar-logo">Explore Majaku</a>
            <nav class="navbar-menu">
                <a href="beranda.php">Beranda</a>
                <a href="pages/tentang.php">Tentang</a>
            </nav>
            <div class="navbar-icons">
                <a href="auth/Login.php" class="nav-icon" aria-label="Profil pengguna" title="Profil pengguna">
                    <i class="bi bi-person-circle"></i>
                </a>
                <a href="auth/Login.php" class="login-button">Login</a>
            </div>
        </div>
    </header>

    <main class="search-page">
        <p class="search-eyebrow">EXPLORE MAJAKU</p>
        <h1>Hasil Pencarian</h1>
        <form class="results-search-form" action="hasil-pencarian.php" method="GET" role="search">
            <label class="visually-hidden" for="keyword">Cari destinasi wisata</label>
            <input
                id="keyword"
                type="search"
                name="keyword"
                placeholder="Nama destinasi, lokasi, atau kategori"
                value="<?= htmlspecialchars($keyword, ENT_QUOTES, "UTF-8") ?>"
                required
            >
            <button type="submit" aria-label="Cari">
                <i class="bi bi-search"></i>
            </button>
        </form>
        <section class="search-results" aria-live="polite" aria-busy="false">
            <p class="search-status" id="searchStatus">
                <?= $keyword === "" ? "Masukkan kata kunci untuk mencari destinasi." : "Sedang mencari destinasi..." ?>
            </p>
            <div class="search-results-grid" id="searchResults"></div>
        </section>
    </main>

    <script>
        const keyword = <?= json_encode($keyword, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const statusElement = document.getElementById('searchStatus');
        const resultsElement = document.getElementById('searchResults');
        const resultsRegion = document.querySelector('.search-results');

        function normalize(value) {
            return String(value ?? '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLocaleLowerCase('id-ID')
                .trim();
        }

        function getWebsiteRecords(markup) {
            const documentFromHome = new DOMParser().parseFromString(markup, 'text/html');
            const records = new Map();

            documentFromHome.querySelectorAll('.destination-section .destination-card').forEach((card) => {
                const name = card.querySelector('h3')?.textContent.trim();
                if (!name) return;

                const location = card.querySelector('.location')?.textContent.replace(/\s+/g, ' ').trim() ?? '';
                const imageElement = card.querySelector('img');
                const imagePath = imageElement?.getAttribute('src');
                const key = normalize(name);
                const existing = records.get(key);

                records.set(key, {
                    name,
                    location: existing?.location || location,
                    description: existing?.description || '',
                    category: existing?.category || '',
                    image: existing?.image || (imagePath ? new URL(imagePath, new URL('beranda.php', window.location.href)).href : ''),
                    sources: new Set([...(existing?.sources ?? []), 'Situs'])
                });
            });

            return Array.from(records.values());
        }

        function getDatabaseRecords(payload) {
            if (payload?.status !== 'success' || !Array.isArray(payload.data)) {
                throw new Error('Respons data wisata tidak valid');
            }

            return payload.data.map((item) => ({
                name: item.nama_wisata ?? '',
                location: [item.lokasi?.alamat, item.lokasi?.domisili].filter(Boolean).join(', '),
                description: item.deskripsi ?? '',
                category: item.kategori?.nama ?? '',
                image: '',
                sources: new Set(['Database'])
            })).filter((item) => item.name);
        }

        function combineRecords(websiteRecords, databaseRecords) {
            const records = new Map();

            [...websiteRecords, ...databaseRecords].forEach((record) => {
                const key = normalize(record.name);
                const existing = records.get(key);

                if (!existing) {
                    records.set(key, { ...record, sources: new Set(record.sources) });
                    return;
                }

                existing.location ||= record.location;
                existing.description ||= record.description;
                existing.category ||= record.category;
                existing.image ||= record.image;
                record.sources.forEach((source) => existing.sources.add(source));
            });

            return Array.from(records.values());
        }

        function renderResults(records, searchTerm) {
            const terms = normalize(searchTerm).split(/\s+/).filter(Boolean);
            const matches = records.filter((record) => {
                const searchableText = normalize([
                    record.name,
                    record.location,
                    record.description,
                    record.category
                ].join(' '));
                return terms.every((term) => searchableText.includes(term));
            });

            matches.sort((first, second) => {
                const normalizedTerm = normalize(searchTerm);
                const firstRank = normalize(first.name) === normalizedTerm ? 0 : normalize(first.name).startsWith(normalizedTerm) ? 1 : 2;
                const secondRank = normalize(second.name) === normalizedTerm ? 0 : normalize(second.name).startsWith(normalizedTerm) ? 1 : 2;
                return firstRank - secondRank || first.name.localeCompare(second.name, 'id');
            });

            resultsElement.replaceChildren();
            matches.forEach((record) => {
                const card = document.createElement('article');
                card.className = 'destination-card search-result-card';

                if (record.image) {
                    const image = document.createElement('img');
                    image.src = record.image;
                    image.alt = record.name;
                    image.loading = 'lazy';
                    card.append(image);
                } else {
                    const placeholder = document.createElement('div');
                    placeholder.className = 'search-result-placeholder';
                    placeholder.setAttribute('aria-hidden', 'true');
                    placeholder.innerHTML = '<i class="bi bi-geo-alt"></i>';
                    card.append(placeholder);
                }

                const content = document.createElement('div');
                content.className = 'card-content';
                const title = document.createElement('h3');
                title.textContent = record.name;
                content.append(title);

                if (record.location) {
                    const location = document.createElement('p');
                    location.className = 'search-result-location';
                    location.textContent = record.location;
                    content.append(location);
                }

                if (record.description) {
                    const description = document.createElement('p');
                    description.className = 'search-result-description';
                    description.textContent = record.description;
                    content.append(description);
                }

                const sources = document.createElement('p');
                sources.className = 'search-result-source';
                sources.textContent = Array.from(record.sources).join(' + ');
                content.append(sources);
                card.append(content);
                resultsElement.append(card);
            });

            return matches.length;
        }

        async function searchDestinations() {
            if (!keyword) return;

            resultsRegion.setAttribute('aria-busy', 'true');
            const sourceResults = await Promise.allSettled([
                fetch('beranda.php').then((response) => {
                    if (!response.ok) throw new Error('Halaman situs tidak dapat dimuat');
                    return response.text();
                }),
                fetch('../Backend/api/wisata.php').then((response) => {
                    if (!response.ok) throw new Error('Data wisata tidak dapat dimuat');
                    return response.json();
                })
            ]);

            const websiteRecords = sourceResults[0].status === 'fulfilled'
                ? getWebsiteRecords(sourceResults[0].value)
                : [];
            let databaseRecords = [];
            if (sourceResults[1].status === 'fulfilled') {
                try {
                    databaseRecords = getDatabaseRecords(sourceResults[1].value);
                } catch {
                    sourceResults[1].status = 'rejected';
                }
            }

            const matchCount = renderResults(combineRecords(websiteRecords, databaseRecords), keyword);
            const failedSources = sourceResults.filter((result) => result.status === 'rejected').length;

            if (matchCount > 0) {
                statusElement.textContent = `${matchCount} destinasi ditemukan${failedSources ? ' dari sumber yang tersedia' : ''}.`;
            } else if (failedSources === sourceResults.length) {
                statusElement.textContent = 'Pencarian tidak dapat memuat data situs maupun database.';
            } else {
                statusElement.textContent = 'Tidak ada destinasi yang cocok dengan kata kunci tersebut.';
            }

            resultsRegion.setAttribute('aria-busy', 'false');
        }

        searchDestinations();
    </script>
</body>
</html>
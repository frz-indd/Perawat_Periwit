<?php
declare(strict_types=1);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$frontEndPosition = strpos($scriptPath, '/FrontEnd/');
$appBasePath = $frontEndPosition !== false
    ? substr($scriptPath, 0, $frontEndPosition)
    : rtrim(dirname(dirname($scriptPath)), '/.');

$pageConfig = [
    'apiDestinationsUrl' => $appBasePath . '/api/admin/destinations',
    'apiCategoriesUrl' => $appBasePath . '/api/admin/categories',
    'apiRegionsUrl' => $appBasePath . '/api/admin/regions',
    'loginUrl' => $appBasePath . '/FrontEnd/auth/login-admin.php',
    'listUrl' => $appBasePath . '/FrontEnd/admin/data-wisata.php',
    'uploadBaseUrl' => $appBasePath . '/',
];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$destinationId = isset($_GET['id'])
    && is_string($_GET['id'])
    && preg_match('/^[A-Za-z0-9_-]{1,16}$/', $_GET['id']) === 1
    ? $_GET['id']
    : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $destinationId !== '' ? 'Edit' : 'Tambah' ?> Data Wisata | Explore Majaku Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Frank+Ruhl+Libre:wght@600;700&display=swap" rel="stylesheet">
    <link href="../CSS/admin-data-wisata.css?v=20261124-validasi-foto-prioritas-v2" rel="stylesheet">
    <link href="../CSS/admin-controls.css?v=20261124-same-dropdown-arrow-v4" rel="stylesheet">
</head>
<body class="admin-form-page">
    <main class="admin-form-container">
        <header class="admin-form-heading">
            <h1 id="formTitle"><?= $destinationId !== '' ? 'Edit Data Wisata' : 'Tambah Data Wisata' ?></h1>
            <p>Lengkapi informasi destinasi wisata dengan detail dan benar.</p>
        </header>

        <div id="formMessage" class="alert d-none" role="alert"></div>
        <form id="destinationForm" class="admin-destination-form" data-api-url="<?= e($pageConfig['apiDestinationsUrl']) ?>" data-api-method="POST" novalidate>
            <input id="destinationId" type="hidden" value="<?= e($destinationId) ?>">
            <div class="admin-form-fields">
                <div class="admin-field">
                    <label for="destinationName">Nama destinasi wisata<span class="required-mark">*</span></label>
                    <input class="form-control" id="destinationName" name="name" type="text" maxlength="160" placeholder="Masukkan nama destinasi wisata" required>
                    <div class="invalid-feedback" id="destinationNameFeedback">Nama wisata wajib diisi.</div>
                </div>
                <div class="admin-field">
                    <label for="destinationRegion">Wilayah<span class="required-mark">*</span></label>
                    <select class="form-select" id="destinationRegion" name="kabupaten_id" required>
                        <option value="">Pilih wilayah</option>
                    </select>
                    <div class="invalid-feedback">Pilih wilayah wisata.</div>
                </div>
                <div class="admin-field">
                    <label for="destinationCategory">Kategori wisata<span class="required-mark">*</span></label>
                    <select class="form-select" id="destinationCategory" name="category_id" required>
                        <option value="">Pilih Kategori</option>
                    </select>
                    <div class="invalid-feedback">Pilih kategori wisata.</div>
                </div>
                <div class="admin-field">
                    <label for="destinationDescription">Deskripsi</label>
                    <input class="form-control" id="destinationDescription" name="description" type="text" maxlength="2000" placeholder="Ketik di sini...">
                </div>
                <div class="admin-field">
                    <label for="destinationDays">Hari</label>
                    <input class="form-control" id="destinationDays" name="operating_days" type="text" maxlength="120" placeholder="Contoh : Senin - Jum’at">
                </div>
                <div class="admin-field">
                    <label for="destinationHours">Jam Operasional</label>
                    <input class="form-control" id="destinationHours" name="operating_hours" type="text" maxlength="120" placeholder="Contoh : 08:00 - 17:00">
                </div>
                <div class="admin-field">
                    <label for="destinationPrice">Harga Tiket masuk (Rp)</label>
                    <input class="form-control" id="destinationPrice" name="price" type="number" min="0" step="1" placeholder="Contoh : 15000">
                    <div class="invalid-feedback">Masukkan harga tiket yang valid.</div>
                </div>
                <div class="admin-field">
                    <label for="destinationMaps">Tautan Google Maps/Embed URL<span class="required-mark">*</span></label>
                    <input class="form-control" id="destinationMaps" name="maps_url" type="url" maxlength="2048" placeholder="https://maps.google.com/..." required>
                    <div class="invalid-feedback">Masukkan URL yang valid.</div>
                </div>
                <div class="admin-field">
                    <label for="destinationLongitude">Longitude<span class="required-mark">*</span></label>
                    <input class="form-control" id="destinationLongitude" name="longitude" type="number" min="-180" max="180" step="any" placeholder="Masukkan koordinat" required>
                    <div class="invalid-feedback">Longitude harus antara -180 dan 180.</div>
                </div>
                <div class="admin-field">
                    <label for="destinationLatitude">Latitude<span class="required-mark">*</span></label>
                    <input class="form-control" id="destinationLatitude" name="latitude" type="number" min="-90" max="90" step="any" placeholder="Masukkan koordinat" required>
                    <div class="invalid-feedback">Latitude harus antara -90 dan 90.</div>
                </div>
            </div>

            <div class="admin-upload-column">
                <input class="visually-hidden" id="destinationImage" name="image" type="file" accept="image/jpeg,image/png">
                <label class="admin-upload-zone" id="uploadZone" for="destinationImage" tabindex="0">
                    <img class="admin-upload-preview d-none" id="imagePreview" alt="Pratinjau foto wisata">
                    <span class="admin-upload-empty" id="uploadEmpty">
                        <svg class="upload-picture-icon" viewBox="0 0 120 100" aria-hidden="true">
                            <rect x="7" y="5" width="93" height="82" rx="13" fill="#176f60"/>
                            <circle cx="34" cy="31" r="10" fill="#d8eeea"/>
                            <path d="M17 72 33 55l18 13 22-25 16 15v20H17z" fill="#d8eeea"/>
                            <circle cx="91" cy="77" r="24" fill="#176f60"/>
                            <path d="M91 65v24M79 77h24" stroke="white" stroke-width="4" stroke-linecap="round"/>
                        </svg>
                        <span class="admin-upload-prompt" id="uploadPrompt">klik ikon tambah foto untuk<br>mengunggah foto atau drag &amp; drop</span>
                        <span class="admin-upload-note"><i class="bi bi-info-circle-fill"></i> format JPG/PNG, maksimal 10MB <span class="required-mark">*</span></span>
                        <span class="admin-upload-filename" id="imageFilename"></span>
                    </span>
                </label>
                <div class="invalid-feedback upload-feedback" id="imageFeedback" role="status" aria-live="polite"></div>

                <!-- Tombol aksi berada di kolom upload agar selalu tepat di bawah foto. -->
                <div class="admin-form-actions">
                    <a class="btn admin-cancel-button" href="<?= e($pageConfig['listUrl']) ?>"><i class="bi bi-x-lg"></i> Batal</a>
                    <button class="btn admin-save-button" id="saveDestinationButton" type="submit"><i class="bi bi-floppy"></i><span><?= $destinationId !== '' ? 'Simpan perubahan' : 'Simpan data' ?></span></button>
                </div>
            </div>
        </form>
    </main>
    <div class="wisata-popup-backdrop" id="wisataPopup" role="dialog" aria-modal="true" aria-labelledby="wisataPopupTitle" aria-hidden="true">
        <div class="wisata-popup">
            <div class="wisata-popup-icon" id="wisataPopupIcon" aria-hidden="true">×</div>
            <h2 class="wisata-popup-title" id="wisataPopupTitle">Gagal di Simpan !</h2>
            <p class="wisata-popup-description" id="wisataPopupText">Wisata gagal ditambahkan ke daftar data wisata. Silahkan coba kembali</p>
            <div class="wisata-popup-actions"><button class="wisata-popup-button" type="button" id="wisataPopupPrimary">Coba Lagi</button></div>
        </div>
    </div>
    <script>
        window.adminWisataConfig = <?= json_encode($pageConfig, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="../Js/admin-form-wisata.js?v=20261124-validasi-foto-prioritas-v2"></script>
<script src="../Js/admin-custom-dropdowns.js?v=20261124-same-dropdown-arrow-v4"></script>
<script src="../Js/api-endpoint-labels.js?v=api-tooltip-3"></script>
</body>
</html>

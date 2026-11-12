<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/../middleware/auth.php';
adminOnly();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['status' => 'method_not_allowed', 'message' => 'Metode tidak diizinkan.']);
    exit;
}

/** Identifiers below are selected only from schema metadata, never request input. */
function dashboardTable(mysqli $db, array $candidates): ?string
{
    foreach ($candidates as $table) {
        $safe = $db->real_escape_string($table);
        $result = $db->query("SHOW TABLES LIKE '{$safe}'");
        if ($result && $result->num_rows > 0) return $table;
    }
    return null;
}

function dashboardColumns(mysqli $db, ?string $table): array
{
    if ($table === null) return [];
    $safe = str_replace('`', '``', $table);
    $result = $db->query("SHOW COLUMNS FROM `{$safe}`");
    $columns = [];
    while ($row = $result->fetch_assoc()) $columns[] = (string) $row['Field'];
    return $columns;
}

function dashboardColumn(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        foreach ($columns as $column) {
            if (strcasecmp($column, $candidate) === 0) return $column;
        }
    }
    return null;
}

function dashboardQuote(?string $identifier): string
{
    if ($identifier === null) throw new RuntimeException('Kolom database yang diperlukan tidak ditemukan.');
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function dashboardCount(mysqli $db, ?string $table, string $label): int
{
    if ($table === null) throw new RuntimeException("Tabel {$label} tidak ditemukan di database.");
    $safe = dashboardQuote($table);
    $result = $db->query("SELECT COUNT(*) AS total FROM {$safe}");
    return (int) ($result->fetch_assoc()['total'] ?? 0);
}

try {
    require_once __DIR__ . '/../database/koneksi.php';
    if (!$conn instanceof mysqli) {
        throw new RuntimeException('Koneksi database tidak tersedia. Periksa Backend/database/database.local.php.');
    }
    $conn->set_charset('utf8mb4');

    // Nama tabel disesuaikan otomatis dengan variasi skema yang umum dipakai project.
    $wisataTable = dashboardTable($conn, ['wisata']);
    $kategoriTable = dashboardTable($conn, ['kategori', 'categories']);
    $ulasanTable = dashboardTable($conn, ['ulasan', 'reviews', 'review']);
    $userTable = dashboardTable($conn, ['user', 'users', 'pengguna']);

    $recent = [];
    if ($wisataTable !== null) {
        $wCols = dashboardColumns($conn, $wisataTable);
        $wId = dashboardColumn($wCols, ['id_wisata', 'Id_wisata', 'id']);
        $wName = dashboardColumn($wCols, ['nama_wisata', 'Nama_wisata', 'nama', 'name']);
        $wRegion = dashboardColumn($wCols, ['domisili', 'wilayah', 'kabupaten', 'lokasi', 'alamat']);
        $wCategory = dashboardColumn($wCols, ['id_kategori', 'Id_kategori', 'category_id']);
        $wCreated = dashboardColumn($wCols, ['created_at', 'tanggal_ditambahkan', 'tanggal_input', 'createdAt']);

        if ($wId !== null && $wName !== null) {
            $select = [
                'w.' . dashboardQuote($wId) . ' AS id_wisata',
                'w.' . dashboardQuote($wName) . ' AS nama_wisata',
                ($wRegion !== null ? 'w.' . dashboardQuote($wRegion) : "'-'") . ' AS wilayah',
                ($wCreated !== null ? 'w.' . dashboardQuote($wCreated) : 'NULL') . ' AS tanggal_ditambahkan',
                "'-' AS kategori",
                "'' AS foto"
            ];
            $joins = '';

            if ($wCategory !== null && $kategoriTable !== null) {
                $kCols = dashboardColumns($conn, $kategoriTable);
                $kId = dashboardColumn($kCols, ['Id_kategori', 'id_kategori', 'id', 'category_id']);
                $kName = dashboardColumn($kCols, ['nama_kategori', 'Nama_kategori', 'nama', 'name']);
                if ($kId !== null && $kName !== null) {
                    $select[4] = 'k.' . dashboardQuote($kName) . ' AS kategori';
                    $joins = ' LEFT JOIN ' . dashboardQuote($kategoriTable) . ' k ON k.' . dashboardQuote($kId) . ' = w.' . dashboardQuote($wCategory);
                }
            }

            $photoTable = dashboardTable($conn, ['foto_wisata', 'wisata_foto', 'destination_photos']);
            if ($photoTable !== null) {
                $fCols = dashboardColumns($conn, $photoTable);
                $fDestination = dashboardColumn($fCols, ['id_wisata', 'Id_wisata', 'wisata_id', 'destination_id']);
                $fUrl = dashboardColumn($fCols, ['link_foto', 'url_foto', 'foto', 'path', 'image_url', 'gambar']);
                $fId = dashboardColumn($fCols, ['Id_foto', 'id_foto', 'id']);
                if ($fDestination !== null && $fUrl !== null) {
                    $photoOrder = $fId !== null ? ' ORDER BY ' . dashboardQuote($fId) . ' ASC' : '';
                    $select[5] = '(SELECT fp.' . dashboardQuote($fUrl) . ' FROM ' . dashboardQuote($photoTable) . ' fp WHERE fp.' . dashboardQuote($fDestination) . ' = w.' . dashboardQuote($wId) . $photoOrder . ' LIMIT 1) AS foto';
                }
            }

            $order = $wCreated !== null
                ? 'w.' . dashboardQuote($wCreated) . ' DESC'
                : 'w.' . dashboardQuote($wId) . ' DESC';
            $sql = 'SELECT ' . implode(', ', $select) . ' FROM ' . dashboardQuote($wisataTable) . ' w' . $joins . ' ORDER BY ' . $order . ' LIMIT 4';
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                $date = $row['tanggal_ditambahkan'] ?? null;
                $recent[] = [
                    'id' => (string) ($row['id_wisata'] ?? ''),
                    'nama_wisata' => (string) ($row['nama_wisata'] ?? '-'),
                    'wilayah' => (string) ($row['wilayah'] ?? '-'),
                    'kategori' => (string) ($row['kategori'] ?? '-'),
                    'foto' => trim((string) ($row['foto'] ?? '')),
                    'tanggal_ditambahkan' => $date ? date('d/m/Y', strtotime((string) $date)) : '-'
                ];
            }
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Ringkasan dashboard berhasil diambil.',
        'data' => [
            'total_wisata' => dashboardCount($conn, $wisataTable, 'wisata'),
            'total_kategori' => dashboardCount($conn, $kategoriTable, 'kategori'),
            'total_ulasan' => dashboardCount($conn, $ulasanTable, 'ulasan'),
            'total_pengguna' => dashboardCount($conn, $userTable, 'user/pengguna'),
            'wisata_terbaru' => $recent
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $exception) {
    error_log('Admin dashboard summary error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'server_error',
        'message' => 'Gagal mengambil ringkasan dashboard. ' . $exception->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}

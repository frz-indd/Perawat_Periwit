<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../middleware/auth.php';
adminOnly();

$respond = static function (int $httpCode, array $payload): void {
    http_response_code($httpCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    $respond(405, [
        'status' => 'method_not_allowed',
        'message' => 'Metode tidak diizinkan. Gunakan GET.'
    ]);
}

/** Return the first matching column name from trusted schema metadata. */
function adminUsersFindColumn(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        foreach ($columns as $column) {
            if (strcasecmp((string) $column, $candidate) === 0) return (string) $column;
        }
    }
    return null;
}

/** Quote identifiers discovered from database metadata, never request input. */
function adminUsersQuote(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

/** Format date as Indonesian short date, e.g. 12 Apr 2025. */
function adminUsersFormatDate(?string $value): ?string
{
    if ($value === null || trim($value) === '' || $value === '0000-00-00 00:00:00') return null;
    $timestamp = strtotime($value);
    if ($timestamp === false) return null;
    $months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

try {
    require_once __DIR__ . '/../database/koneksi.php';
    if (!$conn instanceof mysqli) {
        $respond(503, ['status' => 'service_unavailable', 'message' => 'Koneksi database tidak tersedia.']);
    }
    $conn->set_charset('utf8mb4');

    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT);
    $page = ($page !== false && $page !== null && $page > 0) ? $page : 1;
    $limit = ($limit !== false && $limit !== null && $limit > 0) ? min($limit, 100) : 10;
    $search = trim((string) ($_GET['search'] ?? ''));
    if (function_exists('mb_substr')) $search = mb_substr($search, 0, 100, 'UTF-8');
    else $search = substr($search, 0, 100);

    // Tabel `user` adalah tabel registrasi yang dipakai aplikasi saat ini.
    // Tabel `users` hanya menjadi fallback untuk instalasi yang memakai skema baru.
    $table = null;
    foreach (['user', 'users'] as $candidate) {
        $check = $conn->prepare('SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $check->bind_param('s', $candidate);
        $check->execute();
        $exists = (int) ($check->get_result()->fetch_assoc()['total'] ?? 0) > 0;
        $check->close();
        if ($exists) { $table = $candidate; break; }
    }
    if ($table === null) throw new RuntimeException('Tabel pengguna tidak ditemukan.');

    $columnQuery = 'SHOW COLUMNS FROM ' . adminUsersQuote($table);
    $columnResult = $conn->query($columnQuery);
    $columns = [];
    while ($column = $columnResult->fetch_assoc()) $columns[] = (string) $column['Field'];

    $idColumn = adminUsersFindColumn($columns, ['Id_user', 'id', 'id_user']);
    $nameColumn = adminUsersFindColumn($columns, ['Nama_user', 'name', 'nama', 'nama_user', 'username']);
    $emailColumn = adminUsersFindColumn($columns, ['Email', 'email']);
    $phoneColumn = adminUsersFindColumn($columns, ['No_telepon', 'phone', 'no_telepon', 'phone_number']);
    $roleColumn = adminUsersFindColumn($columns, ['role']);
    $createdColumn = adminUsersFindColumn($columns, ['created_at', 'tanggal_daftar', 'tanggal_dibuat']);

    if ($idColumn === null || $nameColumn === null || $emailColumn === null) {
        throw new RuntimeException('Kolom ID, nama, atau email pengguna tidak ditemukan.');
    }

    $tableSql = adminUsersQuote($table);
    $idSql = adminUsersQuote($idColumn);
    $nameSql = adminUsersQuote($nameColumn);
    $emailSql = adminUsersQuote($emailColumn);
    $where = [];
    $types = '';
    $params = [];

    // Tabel `user` legacy berisi akun pengguna saja; jika role tersedia,
    // tetap batasi hasil ke role user. Untuk tabel `users`, role wajib user.
    if ($roleColumn !== null) {
        $where[] = adminUsersQuote($roleColumn) . ' = ?';
        $types .= 's';
        $params[] = 'user';
    } elseif ($table === 'users') {
        throw new RuntimeException('Kolom role wajib tersedia pada tabel users.');
    }

    if ($search !== '') {
        $searchColumns = [$nameSql, $emailSql];
        $like = '%' . $search . '%';
        $where[] = '(' . implode(' LIKE ? OR ', $searchColumns) . ' LIKE ?)';
        $types .= 'ss';
        $params[] = $like;
        $params[] = $like;
    }

    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $countSql = 'SELECT COUNT(*) AS total FROM ' . $tableSql . $whereSql;
    $countStmt = $conn->prepare($countSql);
    if ($types !== '') $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $countStmt->close();

    $totalPages = $total > 0 ? (int) ceil($total / $limit) : 0;
    if ($totalPages === 0) $page = 1;
    elseif ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $limit;

    $select = [
        $idSql . ' AS id',
        $nameSql . ' AS nama',
        $emailSql . ' AS email',
        ($phoneColumn !== null ? adminUsersQuote($phoneColumn) : 'NULL') . ' AS no_telepon',
        ($roleColumn !== null ? adminUsersQuote($roleColumn) : "'user'") . ' AS role',
        ($createdColumn !== null ? adminUsersQuote($createdColumn) : 'NULL') . ' AS created_at_raw'
    ];
    $dataSql = 'SELECT ' . implode(', ', $select) . ' FROM ' . $tableSql . $whereSql
        . ' ORDER BY ' . ($createdColumn !== null ? adminUsersQuote($createdColumn) . ' DESC, ' : '')
        . $idSql . ' DESC LIMIT ? OFFSET ?';
    $dataStmt = $conn->prepare($dataSql);
    $dataTypes = $types . 'ii';
    $dataParams = array_merge($params, [$limit, $offset]);
    $dataStmt->bind_param($dataTypes, ...$dataParams);
    $dataStmt->execute();
    $result = $dataStmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => (int) $row['id'],
            'nama' => (string) ($row['nama'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'no_telepon' => $row['no_telepon'] !== null ? (string) $row['no_telepon'] : null,
            'role' => (string) ($row['role'] ?? 'user'),
            'created_at' => adminUsersFormatDate(isset($row['created_at_raw']) ? (string) $row['created_at_raw'] : null)
        ];
    }
    $dataStmt->close();

    $respond(200, [
        'status' => 'success',
        'message' => 'Daftar pengguna berhasil diambil.',
        'data' => $users,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_data' => $total,
            'total_pages' => $totalPages,
            'has_previous_page' => $page > 1,
            'has_next_page' => $totalPages > 0 && $page < $totalPages
        ]
    ]);
} catch (Throwable $exception) {
    error_log('Admin users API error: ' . $exception->getMessage());
    $respond(500, ['status' => 'server_error', 'message' => 'Gagal memuat daftar pengguna.']);
}

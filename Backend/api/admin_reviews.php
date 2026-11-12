<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . "/../middleware/auth.php";
adminOnly();

$respond = static function (int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
};

$method = $_SERVER["REQUEST_METHOD"] ?? "GET";
$idRaw = $_GET["id"] ?? null;
$reviewId = null;
if ($idRaw !== null) {
    $reviewId = filter_var($idRaw, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($reviewId === false) {
        $respond(400, ["status" => "invalid_id", "message" => "ID ulasan tidak valid."]);
    }
}

if (!in_array($method, ["GET", "DELETE"], true)) {
    header("Allow: GET, DELETE");
    $respond(405, ["status" => "method_not_allowed", "message" => "Metode yang diizinkan: GET, DELETE."]);
}

try {
    require_once __DIR__ . "/../database/koneksi.php";
    if (!$conn instanceof mysqli) {
        throw new RuntimeException("Koneksi database tidak tersedia.");
    }

    if ($method === "GET") {
        $sql = "
            SELECT
                u.Id_ulasan AS id_ulasan,
                u.Id_user AS id_user,
                usr.Nama_user AS nama_pengguna,
                u.Id_wisata AS id_wisata,
                w.nama_wisata AS nama_wisata,
                u.rating AS rating,
                u.komentar AS komentar,
                u.tanggal_ulasan AS tanggal_ulasan
            FROM ulasan u
            INNER JOIN user usr ON usr.Id_user = u.Id_user
            INNER JOIN wisata w ON w.id_wisata = u.Id_wisata
        ";
        if ($reviewId !== null) {
            $stmt = $conn->prepare($sql . " WHERE u.Id_ulasan = ? LIMIT 1");
            $stmt->bind_param("i", $reviewId);
            $stmt->execute();
            $result = $stmt->get_result();
            $review = $result->fetch_assoc();
            $stmt->close();
            if (!$review) {
                $respond(404, ["status" => "not_found", "message" => "Ulasan tidak ditemukan."]);
            }
            $review["id_ulasan"] = (int) $review["id_ulasan"];
            $review["id_user"] = (int) $review["id_user"];
            $review["rating"] = (float) $review["rating"];
            $respond(200, ["status" => "success", "data" => $review]);
        }

        $sql .= " ORDER BY u.tanggal_ulasan DESC, u.Id_ulasan DESC";
        $result = $conn->query($sql);
        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $row["id_ulasan"] = (int) $row["id_ulasan"];
            $row["id_user"] = (int) $row["id_user"];
            $row["rating"] = (float) $row["rating"];
            $reviews[] = $row;
        }
        $respond(200, ["status" => "success", "data" => $reviews, "total" => count($reviews)]);
    }

    if ($reviewId === null) {
        $respond(400, ["status" => "invalid_id", "message" => "ID ulasan wajib disertakan pada URL."]);
    }

    $conn->begin_transaction();
    $find = $conn->prepare("SELECT Id_wisata FROM ulasan WHERE Id_ulasan = ? FOR UPDATE");
    $find->bind_param("i", $reviewId);
    $find->execute();
    $find->bind_result($destinationId);
    $found = $find->fetch();
    $find->close();

    if (!$found) {
        $conn->rollback();
        $respond(404, ["status" => "not_found", "message" => "Ulasan tidak ditemukan."]);
    }

    $delete = $conn->prepare("DELETE FROM ulasan WHERE Id_ulasan = ?");
    $delete->bind_param("i", $reviewId);
    $delete->execute();
    $delete->close();

    $update = $conn->prepare(
        "UPDATE wisata
         SET rating = (SELECT COALESCE(AVG(rating), 0) FROM ulasan WHERE Id_wisata = ?)
         WHERE id_wisata = ?"
    );
    $update->bind_param("ss", $destinationId, $destinationId);
    $update->execute();
    $update->close();

    $conn->commit();
    $respond(200, ["status" => "success", "message" => "Ulasan berhasil dihapus dan rating destinasi diperbarui.", "data" => ["id_ulasan" => $reviewId]]);
} catch (Throwable $exception) {
    if (isset($conn) && $conn instanceof mysqli) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
    }
    error_log("Admin reviews error: " . $exception->getMessage());
    $respond(500, ["status" => "server_error", "message" => "Gagal memproses data ulasan."]);
}

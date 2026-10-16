<?php

function fetchActiveCategories(mysqli $connection): array
{
    $result = $connection->query(
        "SELECT Id_kategori, nama_kategori, deskripsi, gambar
         FROM kategori
         WHERE aktif = 1
         ORDER BY nama_kategori"
    );

    $categories = $result->fetch_all(MYSQLI_ASSOC);
    foreach ($categories as &$category) {
        $category["Id_kategori"] = (int) $category["Id_kategori"];
    }
    unset($category);

    return $categories;
}

function fetchTourismRows(mysqli $connection, array $filters = []): array
{
    $conditions = ["w.aktif = 1", "k.aktif = 1"];
    $parameters = [];
    $parameterTypes = "";

    if (isset($filters["id_wisata"]) && is_string($filters["id_wisata"])) {
        $conditions[] = "w.id_wisata = ?";
        $parameters[] = $filters["id_wisata"];
        $parameterTypes .= "s";
    }

    if (isset($filters["id_kategori"]) && filter_var($filters["id_kategori"], FILTER_VALIDATE_INT)) {
        $conditions[] = "w.id_kategori = ?";
        $parameters[] = (int) $filters["id_kategori"];
        $parameterTypes .= "i";
    }

    if (isset($filters["keyword"]) && is_string($filters["keyword"]) && trim($filters["keyword"]) !== "") {
        $conditions[] = "(
            w.nama_wisata LIKE ? OR w.deskripsi LIKE ? OR w.alamat LIKE ?
            OR w.domisili LIKE ? OR k.nama_kategori LIKE ?
        )";
        $searchTerm = "%" . trim($filters["keyword"]) . "%";
        for ($index = 0; $index < 5; $index++) {
            $parameters[] = $searchTerm;
            $parameterTypes .= "s";
        }
    }

    foreach (["min_price" => ">=", "max_price" => "<="] as $filter => $operator) {
        if (isset($filters[$filter]) && is_numeric($filters[$filter])) {
            $conditions[] = "w.harga_tiket $operator ?";
            $parameters[] = (float) $filters[$filter];
            $parameterTypes .= "d";
        }
    }

    foreach (["domisili", "id_kategori"] as $filter) {
        if (empty($filters[$filter . "_in"]) || !is_array($filters[$filter . "_in"])) {
            continue;
        }

        $values = array_values($filters[$filter . "_in"]);
        if ($filter === "id_kategori") {
            $values = array_values(array_filter(array_map(
                static fn ($value) => filter_var($value, FILTER_VALIDATE_INT),
                $values
            ), static fn ($value) => $value !== false));
            $parameterType = "i";
        } else {
            $values = array_values(array_filter($values, "is_string"));
            $parameterType = "s";
        }

        if ($values === []) {
            continue;
        }

        $conditions[] = "w.$filter IN (" . implode(",", array_fill(0, count($values), "?")) . ")";
        foreach ($values as $value) {
            $parameters[] = $value;
            $parameterTypes .= $parameterType;
        }
    }

    $sql = "SELECT
                w.id_wisata,
                w.id_kategori,
                w.nama_wisata,
                w.deskripsi,
                w.alamat,
                w.domisili AS wilayah,
                w.harga_tiket,
                w.jam_buka,
                w.jam_tutup,
                w.latitude,
                w.longitude,
                w.Maps_Wisata,
                k.nama_kategori AS kategori,
                k.gambar AS gambar_kategori,
                (
                    SELECT f.link_foto
                    FROM foto_wisata AS f
                    WHERE f.id_wisata = w.id_wisata
                    ORDER BY f.Id_foto
                    LIMIT 1
                ) AS gambar,
                (
                    SELECT AVG(r.rating)
                    FROM ulasan AS r
                    WHERE r.Id_wisata = w.id_wisata
                ) AS rating,
                (
                    SELECT COUNT(*)
                    FROM ulasan AS r
                    WHERE r.Id_wisata = w.id_wisata
                ) AS jumlah_ulasan
            FROM wisata AS w
            INNER JOIN kategori AS k ON k.Id_kategori = w.id_kategori
            WHERE " . implode(" AND ", $conditions) . "
            ORDER BY w.nama_wisata";

    $statement = $connection->prepare($sql);
    if ($parameters !== []) {
        $statement->bind_param($parameterTypes, ...$parameters);
    }
    $statement->execute();
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    foreach ($rows as &$row) {
        $row["id_kategori"] = (int) $row["id_kategori"];
        $row["harga_tiket"] = (float) $row["harga_tiket"];
        $row["rating"] = $row["rating"] === null ? null : (float) $row["rating"];
        $row["jumlah_ulasan"] = (int) $row["jumlah_ulasan"];
        $row["latitude"] = $row["latitude"] === null ? null : (float) $row["latitude"];
        $row["longitude"] = $row["longitude"] === null ? null : (float) $row["longitude"];
    }
    unset($row);

    return $rows;
}

function fetchTourismFacilities(mysqli $connection, string $tourismId): array
{
    $statement = $connection->prepare(
        "SELECT f.nama_fasilitas
         FROM wisata_fasilitas AS wf
         INNER JOIN fasilitas AS f ON f.Id_fasilitas = wf.Id_fasilitas
         WHERE wf.Id_wisata = ?
         ORDER BY f.nama_fasilitas"
    );
    $statement->bind_param("s", $tourismId);
    $statement->execute();
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    return array_column($rows, "nama_fasilitas");
}

function fetchTourismReviews(mysqli $connection, string $tourismId): array
{
    $statement = $connection->prepare(
        "SELECT u.Nama_user AS nama, ul.rating, ul.komentar, ul.tanggal_ulasan
         FROM ulasan AS ul
         INNER JOIN `user` AS u ON u.Id_user = ul.Id_user
         WHERE ul.Id_wisata = ?
         ORDER BY ul.tanggal_ulasan DESC, ul.Id_ulasan DESC"
    );
    $statement->bind_param("s", $tourismId);
    $statement->execute();
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    foreach ($rows as &$row) {
        $row["rating"] = (float) $row["rating"];
    }
    unset($row);

    return $rows;
}

function fetchUserTourismFavorites(mysqli $connection, int $userId): array
{
    $statement = $connection->prepare(
        "SELECT w.id_wisata, w.nama_wisata, w.domisili AS wilayah,
                (
                    SELECT foto.link_foto
                    FROM foto_wisata AS foto
                    WHERE foto.id_wisata = w.id_wisata
                    ORDER BY foto.Id_foto
                    LIMIT 1
                ) AS gambar
         FROM favorit AS favorite
         INNER JOIN wisata AS w ON w.id_wisata = favorite.Id_wisata
         INNER JOIN kategori AS k ON k.Id_kategori = w.id_kategori
         WHERE favorite.Id_user = ? AND w.aktif = 1 AND k.aktif = 1
         ORDER BY favorite.tanggal_ditambahkan DESC, w.nama_wisata"
    );
    $statement->bind_param("i", $userId);
    $statement->execute();
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    return $rows;
}
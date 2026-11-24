<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function fotoNavbarUrl(string $fotoOverride = ''): string
{
    $foto = trim($fotoOverride);
    if ($foto === '') {
        $user = is_array($_SESSION['user'] ?? null) ? $_SESSION['user'] : [];
        foreach ([
            $_SESSION['foto_profil'] ?? '',
            $_SESSION['foto'] ?? '',
            $_SESSION['avatar'] ?? '',
            $user['foto_profil'] ?? '',
            $user['foto'] ?? '',
            $user['avatar'] ?? '',
        ] as $kandidat) {
            if (is_string($kandidat) && trim($kandidat) !== '') {
                $foto = trim($kandidat);
                break;
            }
        }
    }

    if ($foto === '') {
        $userId = filter_var($_SESSION['id_user'] ?? null, FILTER_VALIDATE_INT);
        if ($userId !== false && $userId > 0) {
            try {
                require_once __DIR__ . '/../../Backend/database/koneksi.php';
                if (isset($conn) && $conn instanceof mysqli) {
                    $kolomTersedia = [];
                    $hasilCek = $conn->query("SHOW COLUMNS FROM `user`");
                    if ($hasilCek) {
                        while ($r = $hasilCek->fetch_assoc()) {
                            $kolomTersedia[strtolower((string) $r['Field'])] = (string) $r['Field'];
                        }
                    }
                    $kolomFoto = null;
                    foreach (['foto_profil', 'foto', 'avatar'] as $kandidat) {
                        if (isset($kolomTersedia[$kandidat])) {
                            $kolomFoto = $kolomTersedia[$kandidat];
                            break;
                        }
                    }
                    if ($kolomFoto !== null) {
                        $stmt = $conn->prepare("SELECT `$kolomFoto` FROM `user` WHERE Id_user = ? LIMIT 1");
                        if ($stmt) {
                            $stmt->bind_param('i', $userId);
                            $stmt->execute();
                            $stmt->bind_result($valDb);
                            if ($stmt->fetch() && is_string($valDb) && trim($valDb) !== '') {
                                $foto = trim($valDb);
                                $_SESSION['foto_profil'] = $foto;
                            }
                            $stmt->close();
                        }
                    }
                }
            } catch (Throwable $t) {
                // Ignore DB error for session fallback
            }
        }
    }

    if ($foto === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $foto) === 1) {
        return $foto;
    }
    if (preg_match('#^(?:\.\./)+#', $foto) === 1) {
        return $foto;
    }

    $scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $appRoot = preg_replace('~/FrontEnd/pages/[^/]+$~', '', $scriptPath);
    if (!is_string($appRoot) || $appRoot === $scriptPath) {
        $appRoot = rtrim(str_replace('\\', '/', dirname($scriptPath)), '/');
    }

    return rtrim($appRoot, '/') . '/' . ltrim($foto, '/');
}

function fotoNavbarClass(string $fotoOverride = ''): string
{
    return fotoNavbarUrl($fotoOverride) !== '' ? ' ada-foto' : '';
}

function fotoNavbarMarkup(string $fotoOverride = ''): string
{
    $foto = fotoNavbarUrl($fotoOverride);
    if ($foto === '') {
        return '';
    }

    $src = htmlspecialchars($foto, ENT_QUOTES, 'UTF-8');
    return '<img class="ep-ikon__foto" src="' . $src . '" alt="" referrerpolicy="no-referrer" onerror="this.parentNode.classList.remove(\'ada-foto\');this.remove()">';
}

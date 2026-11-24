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
            $user['foto_profil'] ?? '',
        ] as $kandidat) {
            if (is_string($kandidat) && trim($kandidat) !== '') {
                $foto = trim($kandidat);
                break;
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

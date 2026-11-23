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
    'apiLogoutUrl' => $appBasePath . '/api/admin/logout',
    'loginUrl' => $appBasePath . '/FrontEnd/auth/login-admin.php',
    'formUrl' => $appBasePath . '/FrontEnd/admin/form-wisata.php',
    'uploadBaseUrl' => $appBasePath . '/',
    'homeUrl' => $appBasePath . '/index.php',
];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Wisata | Explore Majaku Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Poppins:wght@400;500;600;700&family=Frank+Ruhl+Libre:wght@600;700&display=swap" rel="stylesheet">
    <link href="../CSS/admin-data-wisata.css?v=20261123-restore-add-button" rel="stylesheet">
    <link href="../CSS/admin-dashboard.css?v=20261123-exact-profile-svg" rel="stylesheet">
</head>
<body class="admin-wisata-page">
    <aside class="admin-sidebar" id="adminSidebar">
        <a class="admin-brand" href="<?= e($pageConfig['homeUrl']) ?>">Explore Majaku</a>
        <button class="sidebar-collapse" id="sidebarCollapse" type="button" aria-label="Ciutkan navigasi">
            <i class="bi bi-chevron-left"></i>
        </button>
        <nav class="admin-nav" aria-label="Navigasi admin">
            <a class="admin-nav__item" href="admin-dashboard.php"><i class="bi bi-house-door"></i><span>Dashboard</span></a>
            <a class="admin-nav__item is-active" href="data-wisata.php" aria-current="page"><i class="bi bi-bar-chart-fill"></i><span>Data Wisata</span></a>
            <a class="admin-nav__item" href="admin-resource.php?section=kategori"><i class="bi bi-grid-fill"></i><span>Kategori</span></a>
            <a class="admin-nav__item" href="admin-resource.php?section=fasilitas"><i class="bi bi-star-fill"></i><span>Fasilitas</span></a>
            <a class="admin-nav__item" href="admin-resource.php?section=ulasan"><i class="bi bi-chat-square-text"></i><span>Ulasan</span></a>
            <a class="admin-nav__item" href="admin-resource.php?section=pengguna"><i class="bi bi-people"></i><span>Pengguna</span></a>
        </nav>
        <div class="admin-sidebar__bottom">
            <a class="admin-nav__item" href="admin-resource.php?section=profil"><svg class="admin-sidebar-profile-svg" width="30" height="30" viewBox="20 10 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M35 10C26.7158 10 20 16.7158 20 25C20 33.2842 26.7158 40 35 40C43.2842 40 50 33.2842 50 25C50 16.7158 43.2842 10 35 10ZM22.093 25C22.0931 22.9202 22.5959 20.8712 23.5584 19.0275C24.5209 17.1838 25.9148 15.6 27.6212 14.411C29.3276 13.222 31.2961 12.4629 33.3591 12.1985C35.422 11.9341 37.5183 12.1721 39.4695 12.8923C41.4206 13.6125 43.1688 14.7936 44.5652 16.3349C45.9616 17.8763 46.9649 19.7322 47.4895 21.7448C48.0142 23.7573 48.0448 25.8669 47.5786 27.8938C47.1124 29.9207 46.1633 31.8049 44.8121 33.386C44.5089 32.3204 43.9483 31.3457 43.1795 30.5479C42.0884 29.4135 40.7237 28.7535 39.5405 28.3851C38.667 28.113 37.8437 28.427 37.3023 28.794C36.8 29.1316 35.9907 29.5349 35 29.5349C34.0093 29.5349 33.2 29.1302 32.6977 28.794C32.1563 28.4284 31.333 28.113 30.4595 28.3851C29.2749 28.7535 27.9116 29.4135 26.8205 30.5479C26.0519 31.3453 25.4913 32.3195 25.1879 33.3847C23.1852 31.0507 22.087 28.0754 22.093 25ZM26.9949 35.1247C27.1009 33.714 27.6367 32.7191 28.3302 31.9977C29.1019 31.194 30.1163 30.6847 31.0819 30.3833C31.1293 30.3721 31.1789 30.3735 31.2256 30.3874C31.3334 30.4168 31.4353 30.4645 31.527 30.5284C32.226 31 33.4442 31.6279 35 31.6279C36.5558 31.6279 37.774 31 38.473 30.5284C38.5648 30.465 38.6667 30.4178 38.7744 30.3888C38.821 30.3745 38.8706 30.3725 38.9181 30.3833C39.8837 30.6847 40.8981 31.194 41.6698 31.9977C42.3633 32.7191 42.8977 33.714 43.0051 35.1247C40.7286 36.9315 37.9064 37.9125 35 37.907C32.0936 37.9125 29.2714 36.9315 26.9949 35.1247ZM31.8605 22.2093C31.8605 20.4651 33.214 19.0698 35 19.0698C36.786 19.0698 38.1395 20.4651 38.1395 22.2093C38.1395 23.9535 36.786 25.3488 35 25.3488C33.214 25.3488 31.8605 23.9535 31.8605 22.2093ZM35 16.9767C34.3113 16.9712 33.6284 17.1027 32.991 17.3637C32.3536 17.6247 31.7746 18.0099 31.2876 18.4969C30.8006 18.9839 30.4154 19.5629 30.1544 20.2003C29.8934 20.8377 29.7619 21.5206 29.7674 22.2093C29.7619 22.898 29.8934 23.5809 30.1544 24.2183C30.4154 24.8557 30.8006 25.4347 31.2876 25.9217C31.7746 26.4087 32.3536 26.7939 32.991 27.0549C33.6284 27.3159 34.3113 27.4474 35 27.4419C35.6887 27.4474 36.3716 27.3159 37.009 27.0549C37.6464 26.7939 38.2254 26.4087 38.7124 25.9217C39.1994 25.4347 39.5846 24.8557 39.8456 24.2183C40.1066 23.5809 40.2381 22.898 40.2326 22.2093C40.2381 21.5206 40.1066 20.8377 39.8456 20.2003C39.5846 19.5629 39.1994 18.9839 38.7124 18.4969C38.2254 18.0099 37.6464 17.6247 37.009 17.3637C36.3716 17.1027 35.6887 16.9712 35 16.9767Z" fill="white"/>
<path d="M66.2017 34V15.8182H72.3452C73.7715 15.8182 74.9375 16.0756 75.843 16.5906C76.7545 17.0996 77.4292 17.7891 77.8672 18.6591C78.3052 19.5291 78.5241 20.4998 78.5241 21.571C78.5241 22.6423 78.3052 23.6159 77.8672 24.4918C77.4351 25.3678 76.7663 26.0662 75.8608 26.587C74.9553 27.1019 73.7952 27.3594 72.3807 27.3594H67.9773V25.4062H72.3097C73.2862 25.4062 74.0704 25.2376 74.6623 24.9002C75.2541 24.5629 75.6832 24.1071 75.9496 23.533C76.2218 22.953 76.358 22.299 76.358 21.571C76.358 20.843 76.2218 20.192 75.9496 19.6179C75.6832 19.0438 75.2512 18.594 74.6534 18.2685C74.0556 17.937 73.2625 17.7713 72.2741 17.7713H68.4034V34H66.2017ZM81.7868 34V20.3636H83.8109V22.4233H83.9529C84.2015 21.7486 84.6513 21.2011 85.3024 20.7809C85.9534 20.3607 86.6873 20.1506 87.5041 20.1506C87.658 20.1506 87.8503 20.1535 88.0811 20.1594C88.312 20.1654 88.4866 20.1742 88.6049 20.1861V22.3168C88.5339 22.299 88.3712 22.2724 88.1167 22.2369C87.8681 22.1954 87.6047 22.1747 87.3265 22.1747C86.6636 22.1747 86.0718 22.3138 85.551 22.592C85.036 22.8642 84.6277 23.243 84.3258 23.7283C84.0299 24.2077 83.8819 24.7552 83.8819 25.3707V34H81.7868ZM96.2132 34.2841C94.9822 34.2841 93.902 33.9911 92.9728 33.4052C92.0495 32.8192 91.3275 31.9995 90.8066 30.946C90.2917 29.8925 90.0343 28.6615 90.0343 27.2528C90.0343 25.8324 90.2917 24.5924 90.8066 23.533C91.3275 22.4736 92.0495 21.6509 92.9728 21.065C93.902 20.479 94.9822 20.1861 96.2132 20.1861C97.4443 20.1861 98.5215 20.479 99.4448 21.065C100.374 21.6509 101.096 22.4736 101.611 23.533C102.132 24.5924 102.392 25.8324 102.392 27.2528C102.392 28.6615 102.132 29.8925 101.611 30.946C101.096 31.9995 100.374 32.8192 99.4448 33.4052C98.5215 33.9911 97.4443 34.2841 96.2132 34.2841ZM96.2132 32.402C97.1484 32.402 97.9178 32.1623 98.5215 31.6829C99.1252 31.2035 99.572 30.5732 99.862 29.7919C100.152 29.0107 100.297 28.1643 100.297 27.2528C100.297 26.3414 100.152 25.4921 99.862 24.7049C99.572 23.9177 99.1252 23.2815 98.5215 22.7962C97.9178 22.3108 97.1484 22.0682 96.2132 22.0682C95.2781 22.0682 94.5087 22.3108 93.905 22.7962C93.3013 23.2815 92.8545 23.9177 92.5645 24.7049C92.2744 25.4921 92.1294 26.3414 92.1294 27.2528C92.1294 28.1643 92.2744 29.0107 92.5645 29.7919C92.8545 30.5732 93.3013 31.2035 93.905 31.6829C94.5087 32.1623 95.2781 32.402 96.2132 32.402ZM111.663 20.3636V22.1392H104.312V20.3636H111.663ZM106.514 34V18.4815C106.514 17.7003 106.697 17.0492 107.064 16.5284C107.431 16.0076 107.908 15.6169 108.494 15.3565C109.079 15.0961 109.698 14.9659 110.349 14.9659C110.864 14.9659 111.284 15.0073 111.61 15.0902C111.935 15.1731 112.178 15.25 112.338 15.321L111.734 17.1321C111.627 17.0966 111.479 17.0522 111.29 16.9989C111.107 16.9457 110.864 16.919 110.562 16.919C109.87 16.919 109.369 17.0936 109.062 17.4428C108.76 17.792 108.609 18.304 108.609 18.9787V34H106.514ZM114.599 34V20.3636H116.694V34H114.599ZM115.665 18.0909C115.256 18.0909 114.904 17.9518 114.608 17.6736C114.318 17.3955 114.173 17.0611 114.173 16.6705C114.173 16.2798 114.318 15.9454 114.608 15.6673C114.904 15.3891 115.256 15.25 115.665 15.25C116.073 15.25 116.422 15.3891 116.712 15.6673C117.008 15.9454 117.156 16.2798 117.156 16.6705C117.156 17.0611 117.008 17.3955 116.712 17.6736C116.422 17.9518 116.073 18.0909 115.665 18.0909ZM122.627 15.8182V34H120.532V15.8182H122.627ZM137.509 34.3196C136.644 34.3196 135.86 34.1568 135.156 33.8313C134.452 33.4999 133.892 33.0234 133.478 32.402C133.064 31.7746 132.857 31.017 132.857 30.1293C132.857 29.348 133.01 28.7147 133.318 28.2294C133.626 27.7382 134.037 27.3535 134.552 27.0753C135.067 26.7971 135.635 26.59 136.257 26.4538C136.884 26.3118 137.514 26.1993 138.148 26.1165C138.976 26.0099 139.648 25.93 140.163 25.8768C140.684 25.8176 141.063 25.7199 141.299 25.5838C141.542 25.4477 141.663 25.2109 141.663 24.8736V24.8026C141.663 23.9266 141.424 23.246 140.944 22.7607C140.471 22.2753 139.752 22.0327 138.787 22.0327C137.787 22.0327 137.002 22.2517 136.434 22.6896C135.866 23.1276 135.467 23.5952 135.236 24.0923L133.247 23.3821C133.602 22.5535 134.076 21.9084 134.668 21.4467C135.265 20.9792 135.916 20.6536 136.621 20.4702C137.331 20.2808 138.029 20.1861 138.716 20.1861C139.154 20.1861 139.657 20.2393 140.225 20.3459C140.799 20.4465 141.353 20.6566 141.885 20.9762C142.424 21.2958 142.871 21.7782 143.226 22.4233C143.581 23.0684 143.759 23.9325 143.759 25.0156V34H141.663V32.1534H141.557C141.415 32.4493 141.178 32.766 140.847 33.1033C140.515 33.4407 140.074 33.7277 139.524 33.9645C138.973 34.2012 138.302 34.3196 137.509 34.3196ZM137.828 32.4375C138.657 32.4375 139.355 32.2747 139.923 31.9492C140.497 31.6237 140.929 31.2035 141.219 30.6886C141.515 30.1737 141.663 29.6321 141.663 29.0639V27.1463C141.575 27.2528 141.379 27.3505 141.077 27.4393C140.781 27.5221 140.438 27.5961 140.048 27.6612C139.663 27.7204 139.287 27.7737 138.92 27.821C138.559 27.8625 138.266 27.898 138.041 27.9276C137.497 27.9986 136.988 28.114 136.514 28.2738C136.047 28.4277 135.668 28.6615 135.378 28.9751C135.094 29.2829 134.952 29.7031 134.952 30.2358C134.952 30.9638 135.221 31.5142 135.76 31.8871C136.304 32.254 136.994 32.4375 137.828 32.4375ZM152.732 34.2841C151.595 34.2841 150.592 33.997 149.722 33.4229C148.852 32.8429 148.172 32.0262 147.68 30.9727C147.189 29.9132 146.943 28.6615 146.943 27.2173C146.943 25.785 147.189 24.5421 147.68 23.4886C148.172 22.4351 148.855 21.6213 149.731 21.0472C150.607 20.4731 151.619 20.1861 152.767 20.1861C153.655 20.1861 154.356 20.334 154.871 20.63C155.392 20.92 155.789 21.2514 156.061 21.6243C156.339 21.9912 156.555 22.2931 156.709 22.5298H156.887V15.8182H158.982V34H156.958V31.9048H156.709C156.555 32.1534 156.336 32.4671 156.052 32.8459C155.768 33.2188 155.363 33.5531 154.836 33.8491C154.309 34.1391 153.608 34.2841 152.732 34.2841ZM153.016 32.402C153.856 32.402 154.567 32.183 155.147 31.745C155.727 31.3011 156.168 30.6886 156.469 29.9073C156.771 29.1201 156.922 28.2116 156.922 27.1818C156.922 26.1638 156.774 25.2731 156.478 24.5096C156.182 23.7402 155.744 23.1424 155.164 22.7163C154.584 22.2842 153.868 22.0682 153.016 22.0682C152.128 22.0682 151.388 22.296 150.796 22.7518C150.21 23.2016 149.77 23.8142 149.474 24.5895C149.184 25.3589 149.039 26.223 149.039 27.1818C149.039 28.1525 149.187 29.0343 149.483 29.8274C149.784 30.6146 150.228 31.242 150.814 31.7095C151.406 32.1712 152.14 32.402 153.016 32.402ZM163.11 34V20.3636H165.134V22.4943H165.312C165.596 21.7663 166.054 21.2011 166.688 20.7986C167.321 20.3903 168.082 20.1861 168.969 20.1861C169.869 20.1861 170.618 20.3903 171.215 20.7986C171.819 21.2011 172.29 21.7663 172.627 22.4943H172.769C173.118 21.79 173.642 21.2307 174.34 20.8164C175.039 20.3962 175.876 20.1861 176.853 20.1861C178.072 20.1861 179.069 20.5678 179.845 21.3313C180.62 22.0889 181.008 23.2696 181.008 24.8736V34H178.913V24.8736C178.913 23.8674 178.637 23.1483 178.087 22.7163C177.536 22.2842 176.888 22.0682 176.143 22.0682C175.184 22.0682 174.441 22.3582 173.914 22.9382C173.388 23.5123 173.124 24.2403 173.124 25.1222V34H170.994V24.6605C170.994 23.8852 170.742 23.2608 170.239 22.7873C169.736 22.3079 169.088 22.0682 168.295 22.0682C167.75 22.0682 167.241 22.2132 166.768 22.5032C166.3 22.7932 165.921 23.1957 165.631 23.7106C165.347 24.2196 165.205 24.8085 165.205 25.4773V34H163.11ZM184.839 34V20.3636H186.934V34H184.839ZM185.904 18.0909C185.495 18.0909 185.143 17.9518 184.847 17.6736C184.557 17.3955 184.412 17.0611 184.412 16.6705C184.412 16.2798 184.557 15.9454 184.847 15.6673C185.143 15.3891 185.495 15.25 185.904 15.25C186.312 15.25 186.661 15.3891 186.951 15.6673C187.247 15.9454 187.395 16.2798 187.395 16.6705C187.395 17.0611 187.247 17.3955 186.951 17.6736C186.661 17.9518 186.312 18.0909 185.904 18.0909ZM192.866 25.7969V34H190.771V20.3636H192.795V22.4943H192.973C193.292 21.8018 193.778 21.2455 194.429 20.8253C195.08 20.3991 195.92 20.1861 196.95 20.1861C197.873 20.1861 198.681 20.3755 199.374 20.7543C200.066 21.1271 200.605 21.6953 200.99 22.4588C201.374 23.2164 201.567 24.1752 201.567 25.3352V34H199.471V25.4773C199.471 24.406 199.193 23.5715 198.637 22.9737C198.081 22.37 197.317 22.0682 196.346 22.0682C195.678 22.0682 195.08 22.2132 194.553 22.5032C194.032 22.7932 193.621 23.2164 193.319 23.7727C193.017 24.3291 192.866 25.0038 192.866 25.7969Z" fill="white"/>
</svg><span>Profil Admin</span></a>
            <button class="admin-nav__item admin-logout" id="logoutButton" type="button" data-api-url="<?= e($pageConfig['apiLogoutUrl']) ?>" data-api-method="POST"><i class="bi bi-box-arrow-right"></i><span>Logout</span></button>
        </div>
    </aside>

    <header class="admin-mobile-bar">
        <button class="mobile-menu-button" id="mobileMenuButton" type="button" aria-label="Buka navigasi"><i class="bi bi-list"></i></button>
        <span class="admin-mobile-brand">Explore Majaku</span>
        <button class="mobile-logout-button" id="mobileLogoutButton" type="button" data-api-url="<?= e($pageConfig['apiLogoutUrl']) ?>" data-api-method="POST" aria-label="Logout"><i class="bi bi-box-arrow-right"></i></button>
    </header>

    <header class="admin-topbar">
        <span><svg class="admin-topbar-profile-svg" width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M25 0C11.193 0 0 11.193 0 25C0 38.807 11.193 50 25 50C38.807 50 50 38.807 50 25C50 11.193 38.807 0 25 0ZM3.48837 25C3.48858 21.5336 4.32646 18.1186 5.93068 15.0458C7.5349 11.973 9.85793 9.33336 12.702 7.35166C15.546 5.36997 18.8269 4.10491 22.2651 3.66421C25.7034 3.22351 29.1972 3.62023 32.4491 4.82057C35.701 6.02091 38.6147 7.98933 40.942 10.5582C43.2694 13.1271 44.9415 16.2204 45.8159 19.5746C46.6903 22.9289 46.7413 26.4448 45.9643 29.823C45.1873 33.2012 43.6055 36.3415 41.3535 38.9767C40.8482 37.2007 39.9138 35.5762 38.6326 34.2465C36.8139 32.3558 34.5395 31.2558 32.5674 30.6419C31.1116 30.1884 29.7395 30.7116 28.8372 31.3233C28 31.886 26.6512 32.5581 25 32.5581C23.3488 32.5581 22 31.8837 21.1628 31.3233C20.2605 30.714 18.8884 30.1884 17.4326 30.6419C15.4581 31.2558 13.186 32.3558 11.3674 34.2465C10.0865 35.5755 9.15211 37.1992 8.64651 38.9744C5.30873 35.0845 3.47834 30.1256 3.48837 25ZM11.6581 41.8744C11.8349 39.5233 12.7279 37.8651 13.8837 36.6628C15.1698 35.3233 16.8605 34.4744 18.4698 33.9721C18.5489 33.9535 18.6314 33.9559 18.7093 33.9791C18.8889 34.0281 19.0588 34.1075 19.2116 34.214C20.3767 35 22.407 36.0465 25 36.0465C27.593 36.0465 29.6233 35 30.7884 34.214C30.9413 34.1083 31.1112 34.0297 31.2907 33.9814C31.3684 33.9574 31.4509 33.9542 31.5302 33.9721C33.1395 34.4744 34.8302 35.3233 36.1163 36.6628C37.2721 37.8651 38.1628 39.5233 38.3419 41.8744C34.5477 44.8859 29.844 46.5208 25 46.5116C20.156 46.5208 15.452 44.8859 11.6581 41.8744ZM19.7674 20.3488C19.7674 17.4419 22.0233 15.1163 25 15.1163C27.9767 15.1163 30.2326 17.4419 30.2326 20.3488C30.2326 23.2558 27.9767 25.5814 25 25.5814C22.0233 25.5814 19.7674 23.2558 19.7674 20.3488ZM25 11.6279C23.8522 11.6186 22.7139 11.8378 21.6517 12.2728C20.5894 12.7078 19.6243 13.3498 18.8127 14.1615C18.001 14.9732 17.359 15.9382 16.924 17.0005C16.489 18.0628 16.2698 19.201 16.2791 20.3488C16.2698 21.4967 16.489 22.6349 16.924 23.6972C17.359 24.7594 18.001 25.7245 18.8127 26.5362C19.6243 27.3479 20.5894 27.9899 21.6517 28.4249C22.7139 28.8598 23.8522 29.0791 25 29.0698C26.1478 29.0791 27.2861 28.8598 28.3483 28.4249C29.4106 27.9899 30.3757 27.3479 31.1873 26.5362C31.999 25.7245 32.641 24.7595 33.076 23.6972C33.511 22.6349 33.7302 21.4967 33.7209 20.3488C33.7302 19.201 33.511 18.0628 33.076 17.0005C32.641 15.9382 31.999 14.9732 31.1873 14.1615C30.3757 13.3498 29.4106 12.7078 28.3483 12.2728C27.2861 11.8378 26.1478 11.6186 25 11.6279Z" fill="#166C5C"/></svg> Admin 1</span>
    </header>

    <main class="admin-main">
        <div class="admin-content">
            <div class="admin-page-heading">
                <div>
                    <h1>Data Wisata</h1>
                    <p>kelola semua data destinasi wisata</p>
                </div>
                <a class="btn admin-add-button" href="<?= e($pageConfig['formUrl']) ?>">
                    <i class="bi bi-plus-circle"></i><span>Tambah wisata</span>
                </a>
            </div>

            <div class="admin-filters" role="search">
                <label class="filter-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input id="searchInput" type="search" placeholder="Cari nama wisata..." aria-label="Cari nama wisata">
                </label>
                <select id="regionFilter" class="form-select" aria-label="Filter wilayah">
                    <option value="">Semua wilayah</option>
                </select>
                <select id="categoryFilter" class="form-select" aria-label="Filter kategori">
                    <option value="">Semua Kategori</option>
                </select>
                <button id="searchButton" class="btn admin-search-button" type="button" data-api-url="<?= e($pageConfig['apiDestinationsUrl']) ?>" data-api-method="GET"><i class="bi bi-search"></i><span>Cari</span></button>
            </div>

            <div id="pageMessage" class="alert admin-alert d-none" role="status"></div>
            <section id="destinationStatePanel" class="destination-state-panel d-none" aria-live="polite"></section>
            <div class="table-responsive admin-table-wrap" id="destinationTableWrap">
                <table class="table admin-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">Foto</th>
                            <th scope="col">Nama wisata</th>
                            <th scope="col">wilayah</th>
                            <th scope="col">Kategori</th>
                            <th scope="col">Harga Tiket<br>masuk</th>
                            <th scope="col">Rating</th>
                            <th scope="col">Lokasi</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="destinationRows">
                        <tr><td class="table-state" colspan="9"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memuat data wisata...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="admin-table-footer" id="destinationTableFooter">
                <p id="resultSummary" class="mb-0" aria-live="polite"></p>
                <nav aria-label="Halaman data wisata">
<ul id="paginationLinks" class="pagination pagination-sm mb-0">
    <li class="page-item disabled">
        <button
            id="previousPage"
            class="page-link"
            type="button"
            data-api-url="<?= e($pageConfig['apiDestinationsUrl']) ?>"
            data-api-method="GET"
            aria-label="Halaman sebelumnya"
            disabled
        >
            <i class="bi bi-chevron-left"></i>
        </button>
    </li>

    <li class="page-item active" aria-current="page">
        <button class="page-link" type="button">1</button>
    </li>

    <li class="page-item">
        <button
            id="nextPage"
            class="page-link"
            type="button"
            data-api-url="<?= e($pageConfig['apiDestinationsUrl']) ?>"
            data-api-method="GET"
            aria-label="Halaman berikutnya"
        >
            <i class="bi bi-chevron-right"></i>
        </button>
    </li>
</ul>
                </nav>
            </div>
        </div>
    </main>

    <div class="admin-sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Modal konfirmasi logout -->
    <div class="logout-modal" id="logoutModal" hidden>
        <div class="logout-modal__backdrop" id="logoutModalBackdrop"></div>
        <div class="logout-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="logoutModalTitle" aria-describedby="logoutModalText">
            <div class="logout-modal__icon" aria-hidden="true">
                <svg viewBox="0 0 34 34"><path fill="currentColor" fill-rule="evenodd" d="M12 0h10a1 1 0 0 1 1 1v5H11V1a1 1 0 0 1 1-1zM13.2 2.6h7.6v3.4h-7.6z"/><rect x="0" y="4.8" width="33.8" height="2.5" rx="1.25" fill="currentColor"/><path d="M3.8 6h26.2v25.6a1.7 1.7 0 0 1-1.7 1.7H5.5a1.7 1.7 0 0 1-1.7-1.7z" fill="currentColor"/><rect x="11.5" y="11.8" width="3" height="14.8" rx="1.5" fill="#c9d6ce"/><rect x="19" y="11.8" width="3" height="14.8" rx="1.5" fill="#c9d6ce"/></svg>
            </div>
            <h2 class="logout-modal__title" id="logoutModalTitle">Apakah Anda yakin ingin keluar dari admin panel Explore MajaKu?</h2>
            <p class="logout-modal__text" id="logoutModalText">Sesi anda akan berakhir</p>
            <div class="logout-modal__actions">
                <button class="logout-modal__button logout-modal__button--confirm" id="logoutConfirmButton" type="button" data-api-url="<?= e($pageConfig['apiLogoutUrl']) ?>" data-api-method="POST">Ya, Keluar</button>
                <button class="logout-modal__button logout-modal__button--cancel" id="logoutCancelButton" type="button">Batal</button>
            </div>
        </div>
    </div>

    <div class="wisata-popup-backdrop" id="wisataPopup" role="dialog" aria-modal="true" aria-labelledby="wisataPopupTitle" aria-hidden="true">
        <div class="wisata-popup">
            <div class="wisata-popup-icon" id="wisataPopupIcon" aria-hidden="true">×</div>
            <h2 class="wisata-popup-title" id="wisataPopupTitle">Gagal di Hapus !</h2>
            <p class="wisata-popup-description" id="wisataPopupText">Data wisata gagal dihapus dari data wisata</p>
            <div class="wisata-popup-actions" id="wisataPopupActions">
                <button class="wisata-popup-button" type="button" id="wisataPopupPrimary">Coba Lagi</button>
            </div>
        </div>
    </div>

    <script>
        window.adminWisataConfig = <?= json_encode($pageConfig, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Js/admin-data-wisata.js?v=20261120-compact"></script>
<script src="../Js/api-endpoint-labels.js?v=api-tooltip-3"></script>
</body>
</html>

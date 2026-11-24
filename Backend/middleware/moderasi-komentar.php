<?php
declare(strict_types=1);

/**
 * Moderasi komentar/ulasan.
 * Pemeriksaan berbasis token utuh setelah normalisasi huruf, angka pengganti,
 * tanda pemisah, dan huruf berulang agar tidak memblokir kata baik secara serampangan.
 */
if (!function_exists('moderasiKomentar')) {
    function moderasiKomentar(string $teks): ?string
    {
        $teks = trim(mb_strtolower($teks, 'UTF-8'));
        if ($teks === '') return null;

        // Normalisasi leetspeak yang umum dipakai untuk menghindari sensor.
        $map = [
            '0' => 'o', '1' => 'i', '!' => 'i', '|' => 'i',
            '3' => 'e', '4' => 'a', '@' => 'a', '5' => 's',
            '$' => 's', '7' => 't', '8' => 'b'
        ];
        $teks = strtr($teks, $map);

        // Satukan huruf yang dipisah simbol: b.a.j.i.n.g.a.n -> bajingan.
        $teks = preg_replace('/(?<=\p{L})[\s._\-*~]+(?=\p{L})/u', '', $teks);
        // Huruf berulang berlebihan: anjiiiiing -> anjing.
        $teks = preg_replace('/(\p{L})\1{2,}/u', '$1$1', $teks);

        $kata = preg_split('/[^\p{L}\p{N}]+/u', $teks, -1, PREG_SPLIT_NO_EMPTY);
        if (!$kata) return null;

        // Daftar dasar berisi umpatan/penghinaan yang umum dipakai sehari-hari
        // dan sejumlah istilah dari ragam Sunda, Jawa, Betawi, serta bahasa gaul.
        // Pemeriksaan token utuh mengurangi false-positive pada kata biasa.
        $buruk = [
            'anjing','anjir','anjay','anjrit','anying','jing','asu','kirik','bajingan',
            'bangsat','brengsek','kampret','keparat','sialan','setan','iblis','laknat',
            'kontol','memek','meki','peler','pepek','ngentot','entot','ngewe','hewes',
            'jembut','toket','tetek','susu','coli','colmek','masturbasi','bokep',
            'ngentod','ngentod','ngentot','ngewe','ngentotin','ngentotin','ngentotlah',
            'fuck','fucking','shit','bitch','bastard','asshole','dick','pussy','motherfucker',
            'tolol','goblok','goblog','gebleg','bego','idiot','dungu','oon','bloon',
            'bodat','budeg','pekok','ndasmu','ndas','matamu','jancuk','jancok','cuk',
            'cok','dancuk','dancok','jangkrik','asem','raimu','asu tenan','ndlogok',
            'kirikmu','celeng','babi','monyet','bangke','tai','taik','tahi','telek',
            'entod','entot','ngentot','ngentod','ngewe','sange','horny','mesum',
            'lonte','jablay','pelacur','perek','sundal','jalang','bencong','banci',
            'waria','homo','lesbi','gay','autis','cacat','idiot','retard',
            'brengsek','brengsekmu','kampungan','miskin hina','hina','hina dina',
            'sundel','setan alas','iblis alas','anak haram','bajingan lu',
            'tai lu','anjing lu','kontol lu','memek lu','goblok lu',
            // Ragam Sunda / Priangan / Cirebon
            'sia','kehed','belegug','gelo','bagong',' kunyuk','monyong','bebel',
            'bedegong','bangor','jorok','kiruh','cicing','modol','ngising','tai',
            'koplok','koplok','goblog','anjing siah','asu siah','kehed sia',
            // Ragam Jawa
            'jancuk','jancok','ndasmu','matamu','raimu','asu','kirik','celeng',
            'goblok','pekok','ndableg','ndablek','mbelgedhes','modar','matane',
            'gathel','gatel','cangkemu','lambemu','kontolmu','memekmu',
            // Betawi / gaul / ragam percakapan
            'monyong','bacot','bacotmu','berisik tolol','sarap','sinting',
            'kurang ajar','kurangajar','brengsek','kampret','keparat','bego lu',
            'goblok lu','tolol lu','anjing lu','bangsat lu','tai lu',
            // Minang / Melayu / ragam daerah yang lazim dikenali
            'pantek','pante','bapuk','palak','barua','kalera','kanciang',
            'bodat','hampeh','lancau','pukimak','kimak',' cibai','sial'
        ];

        // Bersihkan typo spasi tak sengaja di daftar dan siapkan lookup token.
        $set = [];
        foreach ($buruk as $item) {
            $item = trim(preg_replace('/\s+/u', ' ', $item));
            if ($item !== '') $set[$item] = true;
        }

        // Cek kata tunggal (token utuh) setelah normalisasi.
        foreach ($kata as $token) {
            if (isset($set[$token])) {
                return 'Komentar mengandung kata atau umpatan yang tidak diperbolehkan. Mohon gunakan bahasa yang sopan.';
            }
        }

        // Cek frasa multi-kata secara berurutan.
        $normal = ' ' . implode(' ', $kata) . ' ';
        foreach (array_keys($set) as $item) {
            if (strpos($item, ' ') !== false && strpos($normal, ' ' . $item . ' ') !== false) {
                return 'Komentar mengandung kata atau umpatan yang tidak diperbolehkan. Mohon gunakan bahasa yang sopan.';
            }
        }

        return null;
    }
}

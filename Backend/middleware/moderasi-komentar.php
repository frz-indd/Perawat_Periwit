<?php
declare(strict_types=1);

/**
 * Sensor kata kasar pada komentar: komentar tetap dikirim dan disimpan,
 * sedangkan kata yang terdeteksi diganti tanda bintang.
 */
if (!function_exists('sensorKomentar')) {
    function sensorKomentar(string $teks): string
    {
        $buruk = [
            'anjing','anjir','anjay','anjrit','anying','asu','kirik','bajingan',
            'bangsat','brengsek','kampret','keparat','sialan','kontol','memek',
            'meki','peler','pepek','ngentot','entot','ngewe','jembut','coli',
            'colmek','bokep','tolol','goblok','goblog','gebleg','bego','dungu',
            'oon','bloon','bodat','pekok','jancuk','jancok','dancuk','dancok',
            'celeng','bangke','tai','taik','tahi','telek','entod','sange',
            'lonte','jablay','perek','sundal','jalang','sundel','ndableg',
            'ndablek','gathel','cangkemu','lambemu','bacot','pantek','pante',
            'barua','kanciang','lancau','pukimak','kimak','cibai','kehed',
            'belegug','koplok','modol','ngising','kirikmu','kontolmu','memekmu',
            'fuck','fucking','shit','bitch','bastard','asshole','dick','pussy',
            'motherfucker','motherfucking','fuckyou','fuk','sh1t','b1tch',
            'anjinglah','anjingnya','bangsatnya','kontolnya','memeknya',
            'ngentotin','ngentod','ngewean','ngentotlah','brengsekmu',
            'goblokmu','tololmu','bacotmu','matamu','ndasmu','raimu'
        ];

        // Bentuk normal untuk pencocokan, termasuk substitusi angka/simbol.
        $normalisasi = static function (string $kata): string {
            $kata = function_exists('mb_strtolower') ? mb_strtolower($kata, 'UTF-8') : strtolower($kata);
            $kata = strtr($kata, [
                '0'=>'o','1'=>'i','!'=>'i','|'=>'i','3'=>'e','4'=>'a',
                '@'=>'a','5'=>'s','$'=>'s','7'=>'t','8'=>'b'
            ]);
            $kata = preg_replace('/(\p{L})\1{2,}/u', '$1$1', $kata);
            return $kata;
        };

        $lookup = [];
        foreach ($buruk as $kata) {
            $lookup[$normalisasi($kata)] = true;
        }

        // Tangani frasa umum dengan mengganti kata-kata penyusunnya.
        $frasa = [
            'kurang ajar','setan alas','iblis alas','anak haram',
            'tai lu','anjing lu','kontol lu','memek lu','goblok lu',
            'bego lu','tolol lu','bangsat lu','asu siah','kehed sia',
            'anjing siah','berisik tolol'
        ];
        foreach ($frasa as $frasaBuruk) {
            $quoted = preg_quote($frasaBuruk, '/');
            $teks = preg_replace_callback(
                '/(?<![\p{L}\p{N}])' . $quoted . '(?![\p{L}\p{N}])/iu',
                static function ($m) {
                    return preg_replace('/[\p{L}\p{N}]/u', '*', $m[0]);
                },
                $teks
            );
        }

        // Sensor kata per kata. Tanda baca di luar kata tetap dipertahankan.
        return preg_replace_callback(
            '/[\p{L}\p{N}]+/u',
            static function ($m) use ($lookup, $normalisasi) {
                $asli = $m[0];
                return isset($lookup[$normalisasi($asli)])
                    ? str_repeat('*', max(3, function_exists('mb_strlen') ? mb_strlen($asli, 'UTF-8') : strlen($asli)))
                    : $asli;
            },
            $teks
        );
    }
}

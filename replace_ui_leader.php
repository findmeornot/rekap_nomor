<?php

$uiReplacements = [
    'Belum ada leader.' => 'Belum ada Marketing Utama.',
    'Belum ada sub leader.' => 'Belum ada Asisten Marketing.',
    'Pilih leader' => 'Pilih Marketing Utama',
    'Pilih sub leader' => 'Pilih Asisten Marketing',
    'pilih leader' => 'pilih Marketing Utama',
    'pilih sub leader' => 'pilih Asisten Marketing',
    'Cari leader, sub leader,' => 'Cari Marketing Utama, Asisten Marketing,',
    'Cari leader,' => 'Cari Marketing Utama,',
    'berdasarkan leader' => 'berdasarkan Marketing Utama',
    'berdasarkan sub leader' => 'berdasarkan Asisten Marketing',
    'Daftar leader' => 'Daftar Marketing Utama',
    'Daftar sub leader' => 'Daftar Asisten Marketing',
    'Tambah leader' => 'Tambah Marketing Utama',
    'Tambah sub leader' => 'Tambah Asisten Marketing',
    'leader terkait' => 'Marketing Utama terkait',
    'sub leader terkait' => 'Asisten Marketing terkait',
    'Leader belum' => 'Marketing Utama belum',
    'Sub Leader belum' => 'Asisten Marketing belum',
    'Tim leader' => 'Tim Marketing Utama',
    'Tim sub leader' => 'Tim Asisten Marketing',
    'import sub leader' => 'import asisten marketing',
    'import leader' => 'import marketing utama',
    '>Leader<' => '>Marketing Utama<',
    '>Sub Leader<' => '>Asisten Marketing<',
];

$dir = 'resources/views';

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($iterator as $fileInfo) {
    if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
        $content = file_get_contents($fileInfo->getPathname());
        $newContent = strtr($content, $uiReplacements);
        if ($newContent !== $content) {
            file_put_contents($fileInfo->getPathname(), $newContent);
            echo "Updated UI strings in: " . $fileInfo->getPathname() . "\n";
        }
    }
}
echo "Done.\n";

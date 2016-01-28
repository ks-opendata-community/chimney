<?php

$dataPath = dirname(__DIR__) . '/data';

$fh = fopen($dataPath . '/警戒值.csv', 'r');
fgetcsv($fh, 2048);
$alerts = array();
while ($line = fgetcsv($fh, 2048)) {
    if (!isset($alerts[$line[0]])) {
        $alerts[$line[0]] = true;
    }
}
fclose($fh);

$json = json_decode(file_get_contents($dataPath . '/工廠清單.json'), true);
foreach ($json AS $factory) {
    if (!isset($alerts[$factory['管制編號']])) {
        echo "{$factory['city']}/{$factory['管制編號']}/{$factory['工廠']}\n";
    }
}

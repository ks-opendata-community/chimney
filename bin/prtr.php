<?php

$jsonFile = dirname(__DIR__) . '/data/工廠清單.json';
$json = json_decode(file_get_contents($jsonFile), true);
foreach ($json AS $k => $factory) {
    $info = json_decode(file_get_contents('http://kiang.github.io/prtr.epa.gov.tw/data/' . substr($factory['管制編號'], 0, 2) . '/' . $factory['管制編號'] . '.json'), true);
    $json[$k]['工廠'] = $info['info']['name'];
    $json[$k]['地址'] = $info['info']['地址'];
    if (!empty($info['info']['latitude'])) {
        $json[$k]['Lat'] = $info['info']['latitude'];
        $json[$k]['Lng'] = $info['info']['longitude'];
    }
}

file_put_contents($jsonFile, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
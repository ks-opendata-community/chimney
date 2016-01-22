<?php

require_once __DIR__ . '/libs.php';

$currentTime = strtotime(date('Y-m-d'));
$timeEnd = strtotime('2015-11-16');

$obj = new CemsChanghua();

while ($currentTime >= $timeEnd) {
    error_log("getting " . date('Y-m-d', $currentTime));
    $obj->getDay($currentTime);
    $currentTime = strtotime('-1 day', $currentTime);
}
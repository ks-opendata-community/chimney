<?php

require_once __DIR__ . '/libs.php';

$currentTime = strtotime(date('Y-m-d'));
$timeEnd = strtotime('2015-11-16');

$obj = new CemsYilan();

while ($currentTime >= $timeEnd) {
    $obj->getDay($currentTime);
    $currentTime = strtotime('-1 day', $currentTime);
}
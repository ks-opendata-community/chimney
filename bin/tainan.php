<?php

require_once __DIR__ . '/libs.php';

$currentTime = strtotime(date('Y-m-d'));
$timeEnd = strtotime('2016-01-01');

$obj = new CemsTainan();

while ($currentTime >= $timeEnd) {
    $obj->getDay($currentTime);
    $currentTime = strtotime('-1 day', $currentTime);
}
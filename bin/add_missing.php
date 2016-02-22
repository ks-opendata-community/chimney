<?php

date_default_timezone_set('Asia/Taipei');
require_once __DIR__ . '/libs.php';
$objTaichung = new CemsTaichung();
$objYilan = new CemsYilan();
$objTainan = new CemsTainan();
$objChiayi = new CemsChiayi();
$objChanghua = new CemsChanghua();

$missingDates = array('2016-02-21', '2016-02-22');

foreach ($missingDates AS $missingDate) {
    $today = strtotime($missingDate);
    $objTaichung->getDay($today);
    $objYilan->getDay($today);
    $objTainan->getDay($today);
    $objChiayi->getDay($today);
    $objChanghua->getDay($today);
}


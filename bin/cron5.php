<?php

date_default_timezone_set('Asia/Taipei');
require_once __DIR__ . '/libs.php';
$objTaoyuan = new CemsTaoyuan();
$objNewtaipei = new CemsNewtaipei();
$objTaipei = new CemsTaipei();
$objHsinchu = new CemsHsinchu();

$objTaoyuan->getDay();
$objNewtaipei->getDay();
$objTaipei->getDay();
$objHsinchu->getDay();
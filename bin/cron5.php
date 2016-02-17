<?php

date_default_timezone_set('Asia/Taipei');
require_once __DIR__ . '/libs.php';
$objTaoyuan = new CemsTaoyuan();
$objTaoyuan->getDay();
$objNewtaipei = new CemsNewtaipei();
$objNewtaipei->getDay();

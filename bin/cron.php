<?php

date_default_timezone_set('Asia/Taipei');
require_once __DIR__ . '/libs.php';
$objTaichung = new CemsTaichung();
$objYilan = new CemsYilan();
$objTainan = new CemsTainan();
$objChiayi = new CemsChiayi();
$objYunlin = new CemsYunlin();
$objChanghua = new CemsChanghua();
$objKaohsiung = new CemsKaohsiung();

$rootPath = dirname(__DIR__);
$dataPath = $rootPath . '/data/daily';
$time = time();
$now = date('Y-m-d H:i:s', $time);

exec("cd {$rootPath} && /usr/bin/git pull");

$today = strtotime(date('Y-m-d'));
$objTaichung->getDay($today);
$objYilan->getDay($today);
$objTainan->getDay($today);
$objChiayi->getDay($today);
$objYunlin->getDay($today);
$objChanghua->getDay($today);
$objKaohsiung->getDay($today);
copy($dataPath . '/kaohsiung/' . date('Y/m', $time) . '/' . date('Ymd', $time) . '.csv', $dataPath . '/latest.csv');

exec("cd {$rootPath} && /usr/bin/git add -A");

exec("cd {$rootPath} && /usr/bin/git commit --author 'auto commit <noreply@localhost>' -m 'auto update @ {$now}'");

exec("cd {$rootPath} && /usr/bin/git push origin gh-pages");

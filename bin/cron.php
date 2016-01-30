<?php

date_default_timezone_set('Asia/Taipei');
require_once __DIR__ . '/libs.php';
$objTaichung = new CemsTaichung();
$objYilan = new CemsYilan();
$objTainan = new CemsTainan();
$objChiayi = new CemsChiayi();
$objYunlin = new CemsYunlin();
$objChanghua = new CemsChanghua();

$rootPath = dirname(__DIR__);
$rawPath = '/var/www/clients/client0/web8/web';
$dataPath = $rootPath . '/data/daily';
$now = date('Y-m-d H:i:s');

exec("cd {$rootPath} && /usr/bin/git pull");

$latest = array(
    0,
    ''
);
foreach (glob($rawPath . '/AVGR*.csv') AS $csvFile) {
    $info = pathinfo($csvFile);
    // AVGR1041201
    $time = mktime(0, 0, 0, substr($info['filename'], 7, 2), substr($info['filename'], 9, 2), substr($info['filename'], 4, 3) + 1911);
    if ($time > $latest[0]) {
        $latest = array(
            $time,
            $csvFile
        );
    }
    $targetPath = $dataPath . '/kaohsiung/' . date('Y/m', $time);
    if (!file_exists($targetPath)) {
        mkdir($targetPath, 0777, true);
    }
    $targetFile = $targetPath . '/' . date('Ymd', $time) . '.csv';
    if (!file_exists($targetFile)) {
        $c = str_replace(array(' '), array(''), file_get_contents($csvFile));
        $c = implode(',', array(date('Y-m-d', $time), $time, '', '', '')) . "\n" . $c;
        file_put_contents($targetFile, $c);
    }
}
if ($latest[0] > 0) {
    $c = str_replace(array(' '), array(''), file_get_contents($latest[1]));
    $c = implode(',', array(date('Y-m-d', $latest[0]), $latest[0], '', '', '')) . "\n" . $c;

    file_put_contents($dataPath . '/kaohsiung/' . date('Y/m/Ymd', $latest[0]) . '.csv', $c);
    file_put_contents($dataPath . '/latest.csv', $c);
    
    exec("rm -rf {$rootPath}/tmp");
    $objTaichung->getDay($latest[0]);
    $objYilan->getDay($latest[0]);
    $objTainan->getDay($latest[0]);
    $objChiayi->getDay($latest[0]);
    $objYunlin->getDay($latest[0]);
    $objChanghua->getDay($latest[0]);
}

exec("cd {$rootPath} && /usr/bin/git add -A");

exec("cd {$rootPath} && /usr/bin/git commit --author 'auto commit <noreply@localhost>' -m 'auto update @ {$now}'");

exec("cd {$rootPath} && /usr/bin/git push origin gh-pages");

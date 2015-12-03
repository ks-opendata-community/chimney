<?php

$rootPath = dirname(__DIR__);
$rawPath = '/var/www/clients/client0/web8/web';
$dataPath = $rootPath . '/data/daily';
$now = date('Y-m-d H:i:s');

exec("cd {$rootPath} && /usr/bin/git pull");

$latest = array(
    0,
    ''
);
foreach (glob($rawPath . '/*.csv') AS $csvFile) {
    $info = pathinfo($csvFile);
    // AVGR1041201
    $time = mktime(0, 0, 0, substr($info['filename'], 7, 2), substr($info['filename'], 9, 2), substr($info['filename'], 4, 3) + 1911);
    if ($time > $latest[0]) {
        $latest = array(
            $time,
            $csvFile
        );
    }
    $targetPath = $dataPath . '/' . date('Y/m', $time);
    if (!file_exists($targetPath)) {
        mkdir($targetPath, 0777, true);
    }
    $targetFile = $targetPath . '/' . date('Ymd', $time) . '.csv';
    if (!file_exists($targetFile)) {
        copy($csvFile, $targetFile);
    }
}
if($latest[0] > 0) {
    copy($latest[1], $dataPath . '/' . date('Y/m/Ymd', $time) . '.csv');
    copy($latest[1], $dataPath . '/latest.csv');
}

exec("cd {$rootPath} && /usr/bin/git add -A");

exec("cd {$rootPath} && /usr/bin/git commit --author 'auto commit <noreply@localhost>' -m 'auto update @ {$now}'");

exec("cd {$rootPath} && /usr/bin/git push origin gh-pages");

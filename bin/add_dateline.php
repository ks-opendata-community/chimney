<?php

foreach (glob(dirname(__DIR__) . '/data/daily/2015/*/*.csv') AS $csvFile) {
    $info = pathinfo($csvFile);
    $time = mktime(0, 0, 0, substr($info['filename'], 4, 2), substr($info['filename'], 6, 2), substr($info['filename'], 0, 4));

    $c = str_replace(array(' '), array(''), file_get_contents($csvFile));
    $c = implode(',', array(date('Y-m-d', $time), $time, '', '', '')) . "\n" . $c;
    file_put_contents($csvFile, $c);
}
<?php

require_once __DIR__ . '/libs.php';

$obj = new CemsYunlin();

$tmpPath = $obj->tmpPath . '/pool';
if (!file_exists($tmpPath)) {
    mkdir($tmpPath, 0777, true);
}

foreach ($obj->factories AS $f => $items) {
    foreach ($items AS $item) {
        $url = "http://218.161.81.10/epb/CEMSDetail.asp?FNO={$f}&PNO={$item}&DType=1";
        $tmpFile = $tmpPath . '/' . md5($url);
        if (!file_exists($tmpFile)) {
            file_put_contents($tmpFile, file_get_contents($url));
        }
        $c = file_get_contents($tmpFile);
        $pos = strpos($c, '<caption>');
        $pos = strpos($c, '<tr>', $pos);
        $posEnd = strpos($c, '</table>', $pos);
        $lines = explode('</tr>', substr($c, $pos, $posEnd - $pos));
        unset($lines[0]);
        foreach ($lines AS $line) {
            $cols = explode('</td>', $line);
            foreach ($cols AS $k => $v) {
                $cols[$k] = trim(strip_tags($v));
            }
            if (!empty($cols[5]) && $cols[5] !== '-') {
                $cols[0] = preg_replace('/[^0-9]/', '', $cols[0]);
                echo implode("\t", array(
                    $f,
                    $item,
                    $cols[0],
                    $cols[5],
                )) . "\n";
                $cols[0] = '9' . substr($cols[0], 1);
                echo implode("\t", array(
                    $f,
                    $item,
                    $cols[0],
                    $cols[5],
                )) . "\n";
            }
        }
    }
}

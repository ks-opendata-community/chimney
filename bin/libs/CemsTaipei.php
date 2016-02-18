<?php

class CemsTaipei {

    public $rootPath, $dataPath;
    public $codes = array(
        1 => '911', //不透光率(%)
        2 => '922', //二氧化硫(ppm)
        3 => '923', //氮氧化物(ppm)
        4 => '924', //一氧化碳(ppm)
        5 => '926', //氯化氫(ppm)
        6 => '936', //氧氣(%)
    );
    public $resourceUrl = 'http://61.221.35.151/Default.aspx';

    function __construct() {
        $this->rootPath = dirname(dirname(__DIR__));
        $this->dataPath = $this->rootPath . '/data/daily/taipei';
    }

    public function getDay() {
        $currentTime = time(); //could only get current day
        $targetPath = $this->dataPath . date('/Y/m', $currentTime);
        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0777, true);
        }
        $targetFile = $targetPath . date('/Ymd', $currentTime) . '.csv';
        $timeIndexed = $check = array();

        if (file_exists($targetFile)) {
            $fh = fopen($targetFile, 'r');
            fgetcsv($fh, 2048);
            while ($line = fgetcsv($fh, 2048)) {
                $timeKey = $line[3];
                if (!isset($timeIndexed[$timeKey])) {
                    $timeIndexed[$timeKey] = array();
                }
                $timeIndexed[$timeKey][] = $line;
                if (!isset($check[$line[0]])) {
                    $check[$line[0]] = array();
                }
                if (!isset($check[$line[0]][$line[1]])) {
                    $check[$line[0]][$line[1]] = array();
                }
                if (!isset($check[$line[0]][$line[1]][$line[2]])) {
                    $check[$line[0]][$line[1]][$line[2]] = array();
                }
                $check[$line[0]][$line[1]][$line[2]][$timeKey] = true;
            }
            fclose($fh);
        }

        $page = file_get_contents($this->resourceUrl);
        $parts = explode('管制編號：', $page);
        foreach ($parts AS $part) {
            $pos = strpos($part, '連線場所名稱');
            if (false !== $pos) {
                $fcode = trim(strip_tags(substr($part, 0, $pos)));
                $lines = explode('</tr>', $part);
                foreach ($lines AS $line) {
                    $pos = strpos($line, '數據傳輸時間');
                    if (false !== $pos) {
                        $line = str_replace(array('&nbsp;', '◎'), '', $line);
                        $cols = explode('</td>', $line);
                        foreach ($cols AS $k => $v) {
                            $cols[$k] = trim(strip_tags($v));
                        }
                        foreach ($this->codes AS $k => $code) {
                            $v = explode('數據傳輸時間：', $cols[$k]);
                            $timeKey = $v[1];
                            if (!isset($check[$fcode][$cols[0]][$code][$timeKey])) {
                                if (!isset($timeIndexed[$timeKey])) {
                                    $timeIndexed[$timeKey] = array();
                                }
                                $check[$fcode][$cols[0]][$code][$timeKey] = true;
                                $timeIndexed[$timeKey][] = array(
                                    $fcode,
                                    $cols[0],
                                    $code,
                                    $timeKey,
                                    $v[0],
                                );
                            }
                        }
                    }
                }
            }
        }
        ksort($timeIndexed);
        error_log("writing {$targetFile}");
        $fh = fopen($targetFile, 'w');
        fputcsv($fh, array(date('Y-m-d', $currentTime), strtotime(date('Y-m-d', $currentTime)), '', '', ''));
        foreach ($timeIndexed AS $lines) {
            foreach ($lines AS $line) {
                fputcsv($fh, $line);
            }
        }
        fclose($fh);
    }

}

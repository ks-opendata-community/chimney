<?php

class CemsHsinchu {

    public $rootPath, $dataPath;
    public $codes = array(
        1 => '911', //不透光率(%)
        2 => '922', //二氧化硫(ppm)
        3 => '923', //氮氧化物(ppm)
        4 => '936', //氧氣(%)
        5 => '948', //排放流率(Nm3/h)
        6 => '959', //溫度(℃)
    );
    public $resourceUrl = 'http://60.248.81.223/Default.aspx';

    function __construct() {
        $this->rootPath = dirname(dirname(__DIR__));
        $this->dataPath = $this->rootPath . '/data/daily/hsinchu';
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
                            if (count($v) === 2) {
                                $timeKey = $v[1];
                                if (!isset($timeIndexed[$timeKey])) {
                                    $timeIndexed[$timeKey] = array();
                                }
                                if (!isset($check[$fcode][$cols[0]][$code][$timeKey])) {
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
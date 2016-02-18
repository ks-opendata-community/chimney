<?php

class CemsNewtaipei {

    public $rootPath, $dataPath;
    public $factories = array(
        '台電林口' => 'F1700736',
        '南亞樹林' => 'F0701702',
        '八里焚化廠' => 'F2300972',
        '新店焚化廠' => 'F0501686',
        '樹林焚化廠' => 'F0703948',
        '南亞林口' => 'F1600491',
    );
    public $codes = array(
        '不透光率' => '911',
        '二氧化硫' => '922',
        '氮氧化物' => '923',
        '一氧化碳' => '924',
        '煙囪溫度' => '959',
    );
    public $resourceUrl = 'http://data.ntpc.gov.tw/od/data/api/E01CFAED-CA79-46A5-A52B-48093C6EA3FF?$format=json';

    function __construct() {
        $this->rootPath = dirname(dirname(__DIR__));
        $this->dataPath = $this->rootPath . '/data/daily/newtaipei';
    }

    public function getDay() {
        $json = json_decode(file_get_contents($this->resourceUrl), true);
        if (!empty($json)) {
            $firstLine = $json[0];
            $currentTime = mktime(0, 0, 0, intval($firstLine['M_MONTH']), intval($firstLine['M_DAY']), $firstLine['M_YEAR']);
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

            foreach ($json AS $record) {
                foreach ($record AS $k => $v) {
                    $record[$k] = trim($v);
                }
                if (isset($this->factories[$record['ABBR']]) && isset($this->codes[$record['DESP']])) {
                    $fcode = $this->factories[$record['ABBR']];
                    $vcode = $this->codes[$record['DESP']];
                    $timeKey = substr($record['M_TIME'], 0, 4);
                    if (!isset($check[$fcode][$record['DP_NO']][$vcode][$timeKey])) {
                        $check[$fcode][$record['DP_NO']][$vcode][$timeKey] = true;
                        if (!isset($timeIndexed[$timeKey])) {
                            $timeIndexed[$timeKey] = array();
                        }
                        $timeIndexed[$timeKey][] = array(
                            $fcode,
                            $record['DP_NO'],
                            $vcode,
                            $timeKey,
                            $record['M_VAL'],
                        );
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

}
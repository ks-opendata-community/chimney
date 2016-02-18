<?php

class CemsTaoyuan {

    public $rootPath, $dataPath;
    public $factories = array(
        '大園汽電' => 'H4702336',
        '欣榮企業' => 'H4307715',
        '東和鋼鐵' => 'H53A0675',
        '華亞汽電' => 'H4810851',
        '長生電力' => 'H4604568',
        '永豐餘新屋' => 'H5200215',
        '義芳化學' => 'H4601227',
        '國光電力' => 'H4810799',
        '南亞錦興廠' => 'H4601398',
        '中油桃煉' => 'H4803507',
        '伸昌光電' => 'H51A1495',
        '台電大潭廠' => 'H5307960',
    );
    public $codes = array(
        '不透光率' => '911',
        '二氧化硫' => '922',
        '氮氧化物' => '923',
        '一氧化碳' => '924',
        '煙囪溫度' => '959',
    );
    public $resourceUrl = 'http://ckan.tycg.gov.tw/api/3/action/datastore_search?resource_id=28bc4efa-a1c3-4d2e-8705-2010237b82ed';

    function __construct() {
        $this->rootPath = dirname(dirname(__DIR__));
        $this->dataPath = $this->rootPath . '/data/daily/taoyuan';
    }

    public function getDay() {
        $json = json_decode(file_get_contents($this->resourceUrl), true);
        if (!empty($json['result']['records'])) {
            $firstLine = $json['result']['records'][0];
            $currentTime = mktime(0, 0, 0, intval($firstLine['mMonth']), intval($firstLine['mDay']), $firstLine['mYear']);
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

            foreach ($json['result']['records'] AS $record) {
                foreach ($record AS $k => $v) {
                    $record[$k] = trim($v);
                }
                if (isset($this->factories[$record['abbr']]) && isset($this->codes[$record['desp']])) {
                    $fcode = $this->factories[$record['abbr']];
                    $vcode = $this->codes[$record['desp']];
                    $timeKey = substr($record['mTime'], 0, 4);
                    if (!isset($check[$fcode][$record['dpNo']][$vcode][$timeKey])) {
                        $check[$fcode][$record['dpNo']][$vcode][$timeKey] = true;
                        if (!isset($timeIndexed[$timeKey])) {
                            $timeIndexed[$timeKey] = array();
                        }
                        $timeIndexed[$timeKey][] = array(
                            $fcode,
                            $record['dpNo'],
                            $vcode,
                            $timeKey,
                            $record['mVal'],
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
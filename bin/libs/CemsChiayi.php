<?php

class CemsChiayi {

    public $rootPath, $tmpPath, $dataPath;
    public $baseUrl = 'http://118.163.252.55/opencems/';
    public $big5Keys = array(0, 2, 5);
    public $factories = array(
        '嘉義縣鹿草焚化廠' => 'Q7504010',
        '台灣化學纖維股份有限公司新港廠' => 'Q7100254',
        '南亞塑膠股份有限公司嘉義公用廠' => 'Q7600375',
        '嘉惠電力股份有限公司' => 'Q6907286',
    );
    public $codes = array(
        '不透光率' => '911',
        '二氧化硫' => '922',
        '氮氧化物' => '923',
        '一氧化碳' => '924',
        '總還原硫' => '925',
        '氯化氫' => '926',
        'NMHC' => '928',
        '氧氣' => '936',
        '二氧化碳' => '937',
        '排放流率' => '948',
        '溫度' => '959',
    );

    function __construct() {
        $this->rootPath = dirname(dirname(__DIR__));
        $this->dataPath = $this->rootPath . '/data/daily/chiayi';
    }

    public function getDay($currentTime) {
        $targetPath = $this->dataPath . date('/Y/m', $currentTime);
        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0777, true);
        }
        $targetFile = $targetPath . date('/Ymd', $currentTime) . '.csv';
        $twYear = date('Y', $currentTime) - 1911;
        $dayUrl = $this->baseUrl . $twYear . '/R' . $twYear . '-' . date('n-j', $currentTime) . '.csv';
        //工廠名稱、排放管道、監測項目、時間、測值、測值狀態
        $fh = fopen($dayUrl, 'r');
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

        if (false !== $fh) {
            while ($line = fgetcsv($fh, 2048)) {
                foreach ($this->big5Keys AS $k) {
                    $line[$k] = trim(mb_convert_encoding($line[$k], 'utf-8', 'big5'));
                }
                if (isset($this->factories[$line[0]]) && isset($this->codes[$line[2]])) {
                    /*
                     * Array
                      (
                      [0] => ﻿CNO
                      [1] => POLNO
                      [2] => ITEM
                      [3] => TIME
                      [4] => VAL
                      [5] => CODE2
                      )
                     */
                    $timeKey = substr($line[3], 0, 2) . substr($line[3], -2);
                    if (!isset($timeIndexed[$timeKey])) {
                        $timeIndexed[$timeKey] = array();
                    }
                    if (!isset($check[$this->factories[$line[0]]][$line[1]][$this->codes[$line[2]]][$timeKey])) {
                        $check[$this->factories[$line[0]]][$line[1]][$this->codes[$line[2]]][$timeKey] = true;
                        $timeIndexed[$timeKey][] = array(
                            $this->factories[$line[0]],
                            $line[1],
                            $this->codes[$line[2]],
                            $timeKey,
                            $line[4],
                        );
                    }
                } else {
                    print_r($line);
                }
            }
            if (!empty($timeIndexed)) {
                fclose($fh);
                ksort($timeIndexed);
                error_log("writing {$targetFile}");
                $fh = fopen($targetFile, 'w');
                fputcsv($fh, array(date('Y-m-d', $currentTime), $currentTime, '', '', ''));
                foreach ($timeIndexed AS $lines) {
                    foreach ($lines AS $line) {
                        fputcsv($fh, $line);
                    }
                }
                fclose($fh);
            }
        }
    }

}

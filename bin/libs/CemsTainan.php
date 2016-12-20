<?php

class CemsTainan {

    public $rootPath, $tmpPath, $dataPath;
    public $baseUrl = 'http://60.248.54.71/opencems/';
    public $big5Keys = array(0, 2, 5);
    public $factories = array(
        '東展興業股份有限公司' => 'R0500400',
        '台灣汽電共生股份有限公司官田廠' => 'R9702160',
        '榮剛材料科技股份有限公司' => 'R8400827',
        '華新麗華股份有限公司鹽水廠' => 'R8500582',
        '威致鋼鐵工業股份有限公司官田廠' => 'R9701341',
        '森霸電力股份有限公司' => 'R0503072',
        '臺南市永康垃圾資源回收(焚化)廠' => 'R1407702',
        '臺南市城西垃圾焚化廠' => 'D3202577',
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
        $this->dataPath = $this->rootPath . '/data/daily/tainan';
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
            $targetFh = fopen($targetFile, 'r');
            fgetcsv($targetFh, 2048);
            while ($line = fgetcsv($targetFh, 2048)) {
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
            fclose($targetFh);
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
                    if (!isset($check[$this->factories[$line[0]]][$line[1]][$this->codes[$line[2]]][$timeKey])) {

                        $check[$this->factories[$line[0]]][$line[1]][$this->codes[$line[2]]][$timeKey] = true;
                        if (!isset($timeIndexed[$timeKey])) {
                            $timeIndexed[$timeKey] = array();
                        }
                        $timeIndexed[$timeKey][] = array(
                            $this->factories[$line[0]],
                            $line[1],
                            $this->codes[$line[2]],
                            $timeKey,
                            $line[4],
                        );
                    }
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

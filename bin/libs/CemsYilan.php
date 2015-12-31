<?php

class CemsYilan {

    public $rootPath, $tmpPath, $dataPath;
    public $factories = array('G3200778', 'G3200849', 'G4100017', 'G3700791', 'G37A0585', 'G32A0540', 'G3801239',);
    public $baseUrl = 'http://cems.ilepb.gov.tw/OpenData/API/';

    function __construct() {
        $this->rootPath = dirname(__DIR__);
        $this->tmpPath = $this->rootPath . '/tmp/yilan';
        $this->dataPath = $this->rootPath . '/data/daily/yilan';
    }

    public function getDay($currentTime) {
        $dayPath = $this->tmpPath . date('/Y/m/d', $currentTime);
        if (!file_exists($dayPath)) {
            mkdir($dayPath, 0777, true);
        }
        $targetPath = $this->dataPath . date('/Y/m', $currentTime);
        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0777, true);
        }
        $targetFile = $targetPath . date('/Ymd', $currentTime) . '.csv';
        $dayUrl = date('Ymd', $currentTime);
        $timeIndexed = array();

        if (date('Ymd') === date('Ymd', $currentTime)) {
            $baseUrl = $this->baseUrl . 'Realtime/';
        } else {
            $baseUrl = $this->baseUrl . 'Daily/';
        }
        foreach ($this->factories AS $factory) {
            $data = array();
            $tmpFile = $dayPath . '/' . $factory;
            if (!file_exists($tmpFile)) {
                $url = $baseUrl . $factory . '/ALL/' . $dayUrl . '/Csv';
                error_log("getting {$url}");
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_REFERER, 'http://cems.ilepb.gov.tw/OpenData/');
                curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
                curl_setopt($curl, CURLOPT_COOKIESESSION, true);
                curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36');
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HEADER, 1);
                $response = curl_exec($curl);
                $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
                $header = substr($response, 0, $header_size);
                $content = substr($response, $header_size);
                error_log("getting {$header}");
                file_put_contents($tmpFile, $content);
            }
            $tmpFh = fopen($tmpFile, 'r');
            fgetcsv($tmpFh, 2048);
            /*
             * Array
              (
              [0] => ﻿CNO
              [1] => POLNO
              [2] => TIME
              [3] => ITEM
              [4] => CODE2
              [5] => VAL
              )
             */
            while ($line = fgetcsv($tmpFh, 2048)) {
                $timeKey = substr($line[2], 0, 2) . substr($line[2], -2);
                if (!isset($timeIndexed[$timeKey])) {
                    $timeIndexed[$timeKey] = array();
                }
                $timeIndexed[$timeKey][] = array(
                    $line[0],
                    $line[1],
                    $line[3],
                    $timeKey,
                    $line[5],
                );
            }
        }
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

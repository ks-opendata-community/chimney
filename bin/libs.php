<?php

class CemsTaichung {

    public $rootPath, $tmpPath, $dataPath;
    public $factories = array('B2402442', 'L0056153', 'L0200473', 'L0200633', 'L9101748', 'L9200693', 'L9200728', 'L9201289',);
    public $items = array(
        1 => '911', //不透光率 (%)
        2 => '922', //二氧化硫 (ppm)
        3 => '923', //氮氧化物 (ppm)
        4 => '924', //一氧化碳 (ppm)
        5 => '926', //氯化氫 (ppm)
        6 => '927', //VOCs (ppm)
        7 => '936', //氧    氣 (%)
        8 => '937', //二氧化碳 (%)
        9 => '948', //流率 (Nm3/h)
        10 => '959', //溫度 (℃)
    );
    public $baseUrl = 'http://220.130.204.202/program/History/Show_Measure_Detail.asp?Date=';

    function __construct() {
        $this->rootPath = dirname(__DIR__);
        $this->tmpPath = $this->rootPath . '/tmp/taichung';
        $this->dataPath = $this->rootPath . '/data/daily/taichung';
    }

    public function parsePage($page) {
        $pos = strpos($page, '<table x:str');
        $posEnd = strpos($page, '</BODY>', $pos);
        $page = substr($page, $pos, $posEnd - $pos);
        $page = str_replace(array('&nbsp;'), array(' '), $page);
        $lines = explode('<tr>', $page);
        foreach ($lines AS $k => $v) {
            $cols = explode('</td>', $v);
            foreach ($cols AS $ck => $cv) {
                $cols[$ck] = trim(strip_tags($cv));
            }
            $lines[$k] = $cols;
        }
        return $lines;
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
        $dayUrl = $this->baseUrl . (date('Y', $currentTime) - 1911) . date('md', $currentTime);
        $timeIndexed = array();
        foreach ($this->factories AS $factory) {
            $data = array();
            $tmpFile = $dayPath . '/' . $factory;
            if (!file_exists($tmpFile)) {
                file_put_contents($tmpFile, file_get_contents($dayUrl . '&Cno=' . $factory));
            }
            $page = file_get_contents($tmpFile);
            $page = mb_convert_encoding($page, 'utf-8', 'big5');

            $pos = strpos($page, '<SELECT ID=\'PolNo\'');
            $posEnd = strpos($page, '</SELECT>', $pos);
            $pols = explode('</option>', substr($page, $pos, $posEnd - $pos));
            foreach ($pols AS $k => $v) {
                $pols[$k] = trim(strip_tags($v));
                if (empty($pols[$k])) {
                    unset($pols[$k]);
                }
            }
            $lines = $this->parsePage($page);
            if (isset($lines[2][0])) {
                $polParts = explode('：', $lines[2][0]);
                $data[$polParts[1]] = $lines;
            }

            foreach ($pols AS $pol) {
                if (!isset($data[$pol])) {
                    $tmpFile = $dayPath . '/' . $factory . '_' . $pol;
                    if (!file_exists($tmpFile)) {
                        $ch = curl_init();

                        curl_setopt($ch, CURLOPT_URL, $dayUrl . '&Cno=' . $factory);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, 'PolNo=' . $pol);

                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                        $page = curl_exec($ch);

                        curl_close($ch);
                        file_put_contents($tmpFile, $page);
                    }
                    $page = file_get_contents($tmpFile);
                    $page = mb_convert_encoding($page, 'utf-8', 'big5');
                    $data[$pol] = $this->parsePage($page);
                }
            }
            foreach ($data AS $pol => $lines) {
                foreach ($lines AS $line) {
                    if (count($line) === 12) {
                        $timeKey = substr($line[0], 0, 2) . substr($line[0], -2);
                        if (!isset($timeIndexed[$timeKey])) {
                            $timeIndexed[$timeKey] = array();
                        }
                        foreach ($this->items AS $itemKey => $itemCode) {
                            if (strlen($line[$itemKey]) > 0 && $line[$itemKey] !== '---') {
                                ////S1900658,P014,911,0000,1.90
                                if (!preg_match('/[0-9\\.]+/', $line[$itemKey])) {
                                    $line[$itemKey] = '0';
                                }
                                $timeIndexed[$timeKey][] = array(
                                    $factory,
                                    $pol,
                                    $itemCode,
                                    $timeKey,
                                    $line[$itemKey],
                                );
                            }
                        }
                    }
                }
            }
        }
        ksort($timeIndexed);
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
        /*
         * from http://stackoverflow.com/questions/895786/how-to-get-the-cookies-from-a-php-curl-into-a-variable
         */
        $ch = curl_init('http://cems.ilepb.gov.tw/OpenData/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $result = curl_exec($ch);
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
        $cookies = array();
        foreach ($matches[1] as $item) {
            $cookies[] = $item;
        }
        $cookie = implode('; ', $cookies);
        $opts = array(
            'http' => array(
                'method' => "GET",
                'header' => "Accept-language: en-US,en;q=0.5\r\n"
                . "Cache-Control: max-age=0\r\n"
                //. "Connection: keep-alive\r\n"
                . "Cookie: {$cookie}\r\n"
                . "Host: cems.ilepb.gov.tw\r\n"
                . "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:43.0) Gecko/20100101 Firefox/43.0"
            )
        );
        print_r($opts);
        $context = stream_context_create($opts);

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
        //$baseUrl = $this->baseUrl . 'Realtime/';
        foreach ($this->factories AS $factory) {
            $data = array();
            $tmpFile = $dayPath . '/' . $factory;
            if (!file_exists($tmpFile)) {
                echo 'getting ' . $baseUrl . $factory . '/ALL/' . $dayUrl . '/Csv' . "\n";
                file_put_contents($tmpFile, file_get_contents($baseUrl . $factory . '/ALL/' . $dayUrl . '/Csv', false, $context));
                var_dump($http_response_header);
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
            sleep(2);
        }
        ksort($timeIndexed);
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

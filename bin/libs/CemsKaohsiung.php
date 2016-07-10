<?php

class CemsKaohsiung {

    public $rootPath, $tmpPath, $dataPath;

    function __construct() {
        $this->rootPath = dirname(dirname(__DIR__));
        $this->tmpPath = $this->rootPath . '/tmp/kaohsiung';
        $this->dataPath = $this->rootPath . '/data/daily/kaohsiung';
    }

    public function getDay($currentTime) {
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

        $fh = fopen('http://data.kaohsiung.gov.tw/Opendata/DownLoad.aspx?Type=3&CaseNo1=AH&CaseNo2=23&FileType=2&Lang=C&FolderType=', 'r');
        fgetcsv($fh, 2048);
        while ($line = fgetcsv($fh, 2048)) {
            $fno = $line[0];
            $pno = $line[1];
            $code = $line[2];
            $timeKey = $line[3];
            if (!isset($check[$fno][$pno][$code][$timeKey])) {
                if (!isset($timeIndexed[$timeKey])) {
                    $timeIndexed[$timeKey] = array();
                }
                $check[$fno][$pno][$code][$timeKey] = true;
                $timeIndexed[$timeKey][] = array(
                    $fno,
                    $pno,
                    $code,
                    $timeKey,
                    $line[4],
                );
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

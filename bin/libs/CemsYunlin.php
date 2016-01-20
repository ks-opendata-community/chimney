<?php

class CemsYunlin {

    public $rootPath, $tmpPath, $dataPath;
    public $factories = array(
        'P5801719' => array('P101', 'P201', 'P401',),
        'P5801728' => array('P201', 'P301', 'P401', 'P501', 'P601', 'P701',),
        'P5802074' => array('P001', 'P002',),
        'P5802092' => array('PP06', 'PG01', 'PG02',),
        'P5802421' => array('P01A', 'P04A', 'P05A', 'P101', 'P201', 'P301', 'P401', 'P701', 'P801', 'PC01', 'PD01', 'PE01', 'PQ01', 'PS01', 'PT01',),
        'P5802430' => array('PA01', 'PB01', 'PC01', 'PD01', 'PE01',),
        'P5801513' => array('AG01', 'AG02', 'AM01',),
        'P5801602' => array('A502', 'A503', 'A605', 'A606',),
        'P5801728' => array('A001', 'A002', 'A003',),
        'P5801773' => array('AE01', 'AL01', 'AT06', 'AU01',),
        'P5802092' => array('AA01', 'AE01', 'AJ01', 'AK22', 'AM01', 'AP01',),
        'P5802387' => array('A011', 'A211', 'A404',),
        'P5802421' => array('A811', 'A812', 'AR01', 'AR02', 'AR03', 'AR04', 'AR05', 'AR06',),
        'P5802430' => array('A001', 'A002', 'A003', 'A201', 'A202', 'A203', 'A801',),
        'P5805244' => array('AA01', 'AA02',),
        'P5805753' => array('A001', 'A102',),
        'P5805780' => array('A001',),
        'P5806349' => array('A101',),
        'P4600987' => array('P001', 'P002',),
        'P6201053' => array('P001', 'P002',),
    );
    /*
     * http://218.161.81.10/epb/CEMSDetail.asp?FNO=P5801719&PNO=P101
     */
    public $baseUrl = 'http://218.161.81.10/epb/CEMSDetail.asp';

    function __construct() {
        $this->rootPath = dirname(dirname(__DIR__));
        $this->tmpPath = $this->rootPath . '/tmp/yunlin';
        $this->dataPath = $this->rootPath . '/data/daily/yunlin';
    }

    public function getDay($currentTime) {
        $currentTime = time(); //could only get current day
        $dayPath = $this->tmpPath . date('/Y/m/d', $currentTime);
        if (!file_exists($dayPath)) {
            mkdir($dayPath, 0777, true);
        }
        $targetPath = $this->dataPath . date('/Y/m', $currentTime);
        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0777, true);
        }
        $targetFile = $targetPath . date('/Ymd', $currentTime) . '.csv';
        $timeIndexed = array();

        foreach ($this->factories AS $fno => $pnos) {
            foreach ($pnos AS $pno) {
                $page = file_get_contents($this->baseUrl . "?FNO={$fno}&PNO={$pno}");
                $lines = explode('</tr>', $page);
                unset($lines[0]);
                foreach ($lines AS $line) {
                    $cols = explode('</td>', $line);
                    if (count($cols) === 8) {
                        $timeKey = substr($cols[1], -4);
                        if (!isset($timeIndexed[$timeKey])) {
                            $timeIndexed[$timeKey] = array();
                        }
                        $timeIndexed[$timeKey][] = array(
                            $fno,
                            $pno,
                            substr($cols[0], -4, 3),
                            $timeKey,
                            trim(strip_tags($cols[2])),
                        );
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

$o = new CemsYunlin();
$o->getDay(time());

<?php

/*
 * 二氧化硫(222)（公斤/小時）=2.86×10-6*小時平均濃度（ppm）*小時平均流量（Nm3/hr）(248)
 * 氮氧化物(223)（公斤/小時）=2.05×10-6*小時平均濃度（ppm）*小時平均流量（Nm3/hr）(248)
 */
$targets = array('222', '223', '248');
foreach (glob(dirname(__DIR__) . '/data/daily/*/*/*.csv') AS $csvFile) {
    $result = extractCsv($csvFile);
    print_r($result);
}
$targets = array('922', '923', '948');
foreach (glob(dirname(__DIR__) . '/data/daily/taichung/*/*/*.csv') AS $csvFile) {
    $result = extractCsv($csvFile);
    print_r($result);
}

function extractCsv($csvFile) {
    global $targets;
    $data = $result = array();
    $fh = fopen($csvFile, 'r');
    $meta = fgetcsv($fh, 2048);
    while ($line = fgetcsv($fh, 2048)) {
        if (in_array($line[2], $targets)) {
            if (!isset($data[$line[0]])) {
                $data[$line[0]] = array();
            }
            if (!isset($data[$line[0]][$line[1]])) {
                $data[$line[0]][$line[1]] = array();
            }
            if (!isset($data[$line[0]][$line[1]][$line[3]])) {
                $data[$line[0]][$line[1]][$line[3]] = array();
            }
            $data[$line[0]][$line[1]][$line[3]][$line[2]] = $line[4];
        }
    }
    foreach ($data AS $factoryId => $fData) {
        foreach ($fData AS $chimneyId => $cData) {
            foreach ($cData AS $time => $values) {
                if (count($values) === 3 && ($values[$targets[0]] + $values[$targets[1]] + $values[$targets[2]]) > 0) {
                    $num1 = 2.86 * 0.000001 * $values[$targets[0]] * $values[$targets[2]];
                    $num2 = 2.05 * 0.000001 * $values[$targets[1]] * $values[$targets[2]];
                    $result[] = array(
                        $factoryId,
                        $chimneyId,
                        $time,
                        $num1,
                        $num2,
                    );
                }
            }
        }
    }
    return array(
        'meta' => $meta,
        'result' => $result,
    );
}

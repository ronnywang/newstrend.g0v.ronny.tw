<?php

include(__DIR__ . '/../init.inc.php');

foreach (glob(getenv('DATA_PATH') . '/*.gz') as $gz_file) {
    $cmd = 'zgrep --only-match \'"source":"[0-9]*"\' ' . escapeshellarg($gz_file) . '  | sort | uniq -c';

    if (!preg_match('#([0-9]*)\.txt\.gz#', $gz_file, $matches)) {
        continue;
    }
    $date = $matches[1];

    if (count(NewsStat::search(array('date' => $date, 'source' => 0)))) {
        continue;
    }
    $lines = explode("\n", trim(`$cmd`));
    $total = 0;
    foreach ($lines as $line) {
        if (!preg_match('#([0-9]*) "source":"([0-9]*)"#', $line, $matches)) {
            continue;
        }

        NewsStat::insert(array(
            'date' => $date,
            'source' => $matches[2],
            'count' => $matches[1],
        ));
        $total += $matches[1];
        echo "date={$date}, source={$matches[2]}, count={$matches[1]}\n";
    }
    NewsStat::insert(array(
        'date' => $date,
        'source' => 0,
        'count' => $total,
    ));
    echo "date={$date}, source=0, count={$total}\n";
}


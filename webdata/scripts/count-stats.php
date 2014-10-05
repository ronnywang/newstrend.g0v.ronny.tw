<?php

include(__DIR__ . '/../init.inc.php');

// 從 dropbox 看看有沒有新的新聞，倒進 DATA_PATH 中，並且更新各來源總數
$curl = curl_init('https://www.dropbox.com/sh/5wd94w2jn53hotu/AABCxOruOTbhZmihMUr1We83a?dl=0');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$content = curl_exec($curl);
curl_close($curl);

$output = tempnam('', '');
chmod($output, 0644);
for ($i = 1; $i < 7; $i ++) {
    $date = date('Ymd', strtotime('today') - 86400 * $i);
    if (!preg_match("#https://www.dropbox.com([^\"]*)/{$date}\.txt\.gz\?dl=0#", $content, $matches)){
        continue;
    }

    $curl = curl_init($matches[0]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $fp = fopen($output, 'w');
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Wget/1.9.1');
    curl_setopt($curl, CURLOPT_FILE, $fp);
    curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    fclose($fp);
    rename($output, getenv('DATA_PATH') . "/{$date}.txt.gz");
}

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


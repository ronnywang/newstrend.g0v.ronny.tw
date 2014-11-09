<?php

include(__DIR__ . '/../init.inc.php');

// 從 dropbox 看看有沒有新的新聞，倒進 DATA_PATH 中，並且更新各來源總數
$curl = curl_init('https://www.dropbox.com/sh/5wd94w2jn53hotu/AABCxOruOTbhZmihMUr1We83a?dl=0');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$content = curl_exec($curl);
curl_close($curl);

$output = tempnam('', '');
chmod($output, 0644);
if (!preg_match_all("#https://www.dropbox.com[^\"]*/([0-9]*)\.txt\.gz\?dl=0#", $content, $matches)){
    throw new Exception("找不到新聞");
}
foreach ($matches as $i => $match) {
    $date = $matches[1][$i];
    if (file_exists(getenv('DATA_PATH') . "/{$date}.txt.gz")) {
        continue;
    }
    error_log($date);
    $curl = curl_init($matches[0][$i]);
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

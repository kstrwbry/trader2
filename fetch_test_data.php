<?php
declare(strict_types=1);

$filename = 'testdata.ndjson';
$symbol = 'ADAUSDT';
$interval = '1m';
$datasets = 5000000;

$endpoint = 'https://fapi.binance.com/fapi/v1/klines';
$intervalMs = 60 * 1000;
$limit = 1000;

$startTime = (int)(microtime(true) * 1000) - ($datasets * $intervalMs);

// Resume from last line if file exists
if (file_exists($filename)) {
    $file = new SplFileObject($filename);
    $file->seek(PHP_INT_MAX); // seek to last line
    $lastLine = rtrim($file->current(), "\r\n");
    if ($lastLine) {
        try {
            $lastData = json_decode($lastLine, true, 512, JSON_THROW_ON_ERROR);
            $startTime = $lastData['t'] + $intervalMs;
        } catch (Throwable) {
            // ignore parse errors
        }
    }
}

// Open file for append
$fp = fopen($filename, 'ab');
if (!$fp) {
    throw new RuntimeException("Cannot open file: $filename");
}

$maxWeightPerMin = 2400;
$currentWeight = 0;
$startWindow = time();
$parallel = 5;

$downloaded = 0; // count for progress

while ($startTime < (int)(microtime(true) * 1000)) {

    // Rate-limit check
    $elapsed = time() - $startWindow;
    if ($elapsed < 60 && $currentWeight + ($parallel * 5) > $maxWeightPerMin) {
        sleep(60 - $elapsed);
        $currentWeight = 0;
        $startWindow = time();
    }

    $mh = curl_multi_init();
    $handles = [];
    $handleMap = [];

    // Prepare parallel requests
    for ($i = 0; $i < $parallel; $i++) {
        $url = $endpoint
            . '?symbol=' . urlencode($symbol)
            . '&interval=' . $interval
            . '&limit=' . $limit
            . '&startTime=' . $startTime;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HEADER => true,
        ]);

        curl_multi_add_handle($mh, $ch);
        $handles[] = $ch;
        $handleMap[(int)$ch] = $startTime;

        $startTime += $limit * $intervalMs;
    }

    // Execute parallel requests
    $running = 0;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);

    // Collect responses mapped by startTime
    $responses = [];
    foreach ($handles as $ch) {
        $response = curl_multi_getcontent($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $headerSize);

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($status !== 200) {
            curl_multi_close($mh);
            fclose($fp);
            throw new RuntimeException("HTTP $status: $body");
        }

        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        $responses[$handleMap[(int)$ch]] = $data;

        // Update weight from headers
        if (preg_match('/X-MBX-USED-WEIGHT-1M: (\d+)/i', substr($response, 0, $headerSize), $matches)) {
            $currentWeight = (int)$matches[1];
        } else {
            $currentWeight += 5;
        }

        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }

    curl_multi_close($mh);

    // Sort responses by startTime to preserve chronological order
    ksort($responses);

    // Write sorted data to file
    foreach ($responses as $dataSet) {
        foreach ($dataSet as $kline) {
            $entry = [
                't' => $kline[0],
                'T' => $kline[6],
                's' => $symbol,
                'i' => $interval,
                'f' => null,
                'L' => null,
                'o' => $kline[1],
                'h' => $kline[2],
                'l' => $kline[3],
                'c' => $kline[4],
                'v' => $kline[5],
                'n' => $kline[8],
                'x' => '1',
                'q' => $kline[7],
                'V' => $kline[9],
                'Q' => $kline[10],
            ];

            fwrite($fp, json_encode($entry, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
            $downloaded++;
        }
    }

    // Progress
    $percent = min(($downloaded / $datasets) * 100, 100);
    $memory = memory_get_usage(true) / 1024 / 1024;
    echo sprintf("[%s] %.2f%% (%d / %d) | mem: %.2f MB\n", $symbol, $percent, $downloaded, $datasets, $memory);

    usleep(100000);
}

fclose($fp);

echo "\nFinished downloading.\n\n";

<?php
declare(strict_types=1);

$symbol = 'ADAUSDT';
$interval = '1m';
$limit = 1000;
$loops = 100;

$endpoint = 'https://fapi.binance.com/fapi/v1/klines';
$intervalMs = 60 * 1000;

// start far enough in the past to allow 100k candles
$startTime = (int)(microtime(true) * 1000) - ($loops * $limit * $intervalMs);

$mapped = [];

for ($i = 0; $i < $loops; $i++) {

    echo sprintf('Fetching datasets (%s of %s) ...', $i + 1, $loops) . PHP_EOL;

    $url = $endpoint
        . '?symbol=' . urlencode($symbol)
        . '&interval=' . $interval
        . '&limit=' . $limit
        . '&startTime=' . $startTime;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new RuntimeException('Curl error: ' . curl_error($ch));
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200) {
        throw new RuntimeException("HTTP $status from Binance: $response");
    }

    $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

    if (!$data) {
        break;
    }

    foreach ($data as $kline) {

        $mapped[] = [
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
    }

    // move to next candle after last returned
    $lastOpenTime = $data[count($data) - 1][0];
    $startTime = $lastOpenTime + $intervalMs;

    // gentle rate-limit protection
    usleep(150000);
}

echo "Mapped candles: " . count($mapped) . PHP_EOL;

file_put_contents('testdata.json', json_encode($mapped));

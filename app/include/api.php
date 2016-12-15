<?php
/**
 * @param int $type (1 = xbox, 2 = psn)
 * @param int $account
 * @param int $character
 * @param string $apiKey
 */
function get_trials_ticket($type, $account, $character, $apiKey)
{
    if ($type == 'xbox') {
        $type = 1;
    } else {
        $type = 2;
    }

    $url = 'https://www.bungie.net/Platform/Destiny/' . $type . '/Account/' . $account . '/Character/' . $character . '/Advisors/V2/';
    $user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_COOKIEFILE => COOKIE_PATH,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => $user_agent,
        CURLOPT_HTTPHEADER => [
            'X-API-Key: ' . $apiKey
        ]
    ]);
    $dataString = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    // Check Data
    $data = json_decode($dataString, true);
    if (!$data) {
        return false;
    }

    if (isset($data['Response'])
        && isset($data['Response']['data'])
        && isset($data['Response']['data']['activities'])
        && isset($data['Response']['data']['activities']['trials'])
        && isset($data['Response']['data']['activities']['trials']['extended'])
        && isset($data['Response']['data']['activities']['trials']['extended']['scoreCard'])) {
        $ticket = $data['Response']['data']['activities']['trials']['extended']['scoreCard'];
    } else {
        return false;
    }
    return $ticket;
}

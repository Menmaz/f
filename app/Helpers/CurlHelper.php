<?php

namespace App\Helpers;

class CurlHelper
{
    public static function fetchHtmlViaNoProxy($url, $timeout = 200, $connectTimeout = 200)
    {
        $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
    
            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                curl_close($ch);
                return "Error: $error_msg";
            }
            
            curl_close($ch);
            return $response;
    }


public static function fetchHtmlViaProxy($url, $iconHref = "https://nettruyencc.com/public/assets/images/favicon.png")
{
    // return self::fetchHtmlViaNoProxy($url);

    $api_url = 'https://api.proxyscrape.com/v3/accounts/freebies/scraperapi/request';
    $apiKey = 'fad59b12-0017-4ade-9ebe-560498e39457';

    $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.91 Safari/537.36'
    ];
        
    $data = json_encode(array(
            "url" => $url,
            "browserHtml" => true,
    ));
        
    $ch = curl_init();
        
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgents[array_rand($userAgents)]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        
    $headers = array(
            'Content-Type: application/json',
            'X-Api-Key: ' . $apiKey
    );
        
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
    $response = curl_exec($ch);
    curl_close($ch);
    if ($response === false) {
        echo 'Curl error: ' . curl_error($ch);
    } else {
        $responseData = json_decode($response, true);
            
        if (isset($responseData['data']['browserHtml'])) {
            return $responseData['data']['browserHtml'];
        } elseif (isset($response_data['data']['httpResponseBody'])) {
            return base64_decode($response_data['data']['httpResponseBody']);
        } else {
            echo 'Không có dữ liệu hợp lệ trong phản hồi.';
        }
    }
}


}

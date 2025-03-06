<?php
$ch = curl_init("https://www.google.com");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if ($response === false) {
    echo "cURL error: " . curl_error($ch);
} else {
    echo "Success! Page length: " . strlen($response);
}

curl_close($ch);

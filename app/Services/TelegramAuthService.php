<?php

namespace App\Services;

class TelegramAuthService
{
    public function validate($authData, $botToken)
{
    if (!$authData) return false; // Stop the crash if no data is sent

    parse_str($authData, $data);
    
    if (!isset($data['hash'])) return false; // Stop the crash if hash is missing

    $checkHash = $data['hash'];
    unset($data['hash']);

    ksort($data);

    $dataCheckString = "";
    foreach ($data as $key => $value) {
        $dataCheckString .= "$key=$value\n";
    }
    $dataCheckString = rtrim($dataCheckString, "\n");

    // Secret Key must be hashed with "WebAppData" string
    $secretKey = hash_hmac('sha256', $botToken, "WebAppData", true);
    $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

    return hash_equals($hash, $checkHash);
}
}
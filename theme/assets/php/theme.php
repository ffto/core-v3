<?php
function to_currency ($amount, $from, $to){
    // [ ] cache the results everyday

    $api_key      = 'd6be99f66854429db63a66b84f03f7a4';
    $api_endpoint = "https://open.er-api.com/v6/latest/{$from}?app_id={$api_key}";
    $json         = http($api_endpoint);
    $to           = strtoupper($to);
    $rate         = _get($json, "rates.{$to}");
    $converted    = $rate ? number_format($amount * $rate, 2) . ' ' . $to : null;

    return $converted;
}
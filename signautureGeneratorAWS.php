<?php

function sign($key, $msg){
    return hash_hmac('sha256', $msg, $key, true);
}

function getSignatureKey($key, $dateStamp, $regionName, $serviceName){
    $kDate = sign("AWS4$key", $dateStamp);
    echo $kDate."\n";
    $kRegion = sign($kDate, $regionName);
    echo $kRegion."\n";
    $kService = sign($kRegion, $serviceName);
    echo $kService."\n";
    $kSigning = sign($kService, 'aws4_request');
    echo $kSigning."\n";
    return $kSigning;
}

$method = 'GET';
$service = 'execute-api';
$host = '02a4yfeonl.execute-api.eu-west-2.amazonaws.com';
$region = 'eu-west-2';
$endpoint = 'https://02a4yfeonl.execute-api.eu-west-2.amazonaws.com/dev/viewMission';
$request_parameters = 'id=1';

$access_key = "";
$secret_key = "";

$amzdate = gmdate('Ymd\THis\Z');
$datestamp = gmdate('Ymd');

$canonical_uri = '/dev/viewMission';
$canonical_query_string = $request_parameters;
$canonical_headers = "host:$host\nx-amz-date:$amzdate\n";
$signed_headers = 'host;x-amz-date';
$payload_hash = hash('sha256', "");

$canonical_request = "$method\n$canonical_uri\n$canonical_query_string\n$canonical_headers\n$signed_headers\n$payload_hash";
$canonical_request_hash = hash('sha256', $canonical_request);

$algorithm = 'AWS4-HMAC-SHA256';
$credential_scope = "$datestamp/$region/$service/aws4_request";

$string_to_sign = "$algorithm\n$amzdate\n$credential_scope\n$canonical_request_hash";

$signing_key = getSignatureKey($secret_key, $datestamp, $region, $service);

$signature = hash_hmac("sha256", $string_to_sign, $signing_key);

echo $signature."\n";

$authorization_header = "$algorithm Credential=$access_key/$credential_scope, SignedHeaders=$signed_headers, Signature=$signature";

$headers = array("x-amz-date: $amzdate", "Authorization: $authorization_header");

var_dump($headers);

$request_url = "$endpoint?$request_parameters";

echo "\n".$request_url."\n";

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $request_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => $headers
));


$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    echo "cURL Error #:" . $err;
} else {
    echo "No Error\n";
    echo $response;
    $results = $response;
}
//return $results;





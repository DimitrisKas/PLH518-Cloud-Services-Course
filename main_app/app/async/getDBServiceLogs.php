<?php

$ch = curl_init();
$url = "http://db-service/logs";
curl_setopt($ch,CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// Execute post
$result = curl_exec($ch);
echo $result;

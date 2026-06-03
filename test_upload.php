<?php
$file = curl_file_create('c:/Users/madhu/OneDrive/Desktop/watxio/watxio-rn/app.json', 'audio/m4a', 'voice.m4a');
$ch = curl_init('http://127.0.0.1:8000/api/v1/mobile/media/upload');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $file]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// bypass auth for testing, or we will get 401. Let's see what we get.
$response = curl_exec($ch);
echo $response;

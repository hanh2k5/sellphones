<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
if (!$apiKey) die("No API KEY");

$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
$data = json_decode($response, true);
if (isset($data['models'])) {
    foreach ($data['models'] as $m) {
        if (strpos($m['name'], 'gemini') !== false) {
            echo $m['name'] . "\n";
        }
    }
} else {
    echo "Error: " . $response;
}

<?php
require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['GEMINI_API_KEY'];
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

$response = file_get_contents($url);
$data = json_decode($response, true);

echo "DANH SÁCH MODEL BẠN CÓ THỂ DÙNG:\n";
foreach ($data['models'] as $model) {
    echo "- " . $model['name'] . " (Hỗ trợ: " . implode(', ', $model['supportedGenerationMethods']) . ")\n";
}

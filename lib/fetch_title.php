<?php
require '../config.php';

header('Content-Type: application/json');

$url = $_GET['url'] ?? '';

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['title' => null]);
    exit;
}

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (ReviewBot)'
]);

$html = curl_exec($ch);
curl_close($ch);

$title = null;

if ($html && preg_match('/<title>(.*?)<\/title>/is', $html, $m)) {
    $title = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5));
}

echo json_encode(['title' => $title]);

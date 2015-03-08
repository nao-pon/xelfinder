<?php

require(__DIR__ . '/../vendor/autoload.php');
include 'ExampleTools.php';

$consumerKey = $_SERVER['argv'][1];
$consumerSecret = $_SERVER['argv'][2];
$accessToken = $_SERVER['argv'][3];
$tokenSecret = $_SERVER['argv'][4];
$cloudPath = $_SERVER['argv'][5];

// Create a cloud api connection to copy
$ca = new \Barracuda\Copy\API($consumerKey, $consumerSecret, $accessToken, $tokenSecret);

print("Listing $cloudPath\n");

$children = $ca->listPath($cloudPath);

foreach ($children as $child) {
    printf("%5.5s %10.10s ", $child->{"type"}, humanFileSize($child->{"size"}));
    echo basename($child->{"path"}) . PHP_EOL;
}

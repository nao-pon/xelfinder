<?php

require(__DIR__ . '/../vendor/autoload.php');
include 'ExampleTools.php';

$consumerKey = $_SERVER['argv'][1];
$consumerSecret = $_SERVER['argv'][2];
$accessToken = $_SERVER['argv'][3];
$tokenSecret = $_SERVER['argv'][4];
$localPath = $_SERVER['argv'][5];
$cloudPath = $_SERVER['argv'][6];

// Create a cloud api connection to copy
$ca = new \Barracuda\Copy\API($consumerKey, $consumerSecret, $accessToken, $tokenSecret);

// Ensure the local file exists
$fh = fopen($localPath, "rb");
if (!$fh) {
    die("Failed to open $localPath\n");
}

// Send it up, 1MB at a time
print("Sending $localPath to $cloudPath\n");

$parts = array();
while ($data = fread($fh, 1024 * 1024)) {
    array_push($parts, $ca->sendData($data));
}
fclose($fh);

// Now update the file in the cloud
$ca->createFile($cloudPath, $parts);

print("Successfully created/modified file $cloudPath . \n");

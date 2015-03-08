<?php

require(__DIR__ . '/../vendor/autoload.php');
require 'ExampleTools.php';

$consumerKey = $_SERVER['argv'][1];
$consumerSecret = $_SERVER['argv'][2];
$accessToken = $_SERVER['argv'][3];
$tokenSecret = $_SERVER['argv'][4];
$cloudPath = $_SERVER['argv'][5];
$localPath = $_SERVER['argv'][6];

// Create a cloud api connection to copy
$ca = new \Barracuda\Copy\API($consumerKey, $consumerSecret, $accessToken, $tokenSecret);

// Ensure the file exists
$files = $ca->listPath($cloudPath, array("include_parts" => true));

if (!$files) {
    die("Object " . $cloudPath . " doesn't exist\n");
}

// Found it, verify its a file
foreach ($files as $file) {
    if ($file->{"type"} != "file") {
        die("Object " . $file->{"path"} . " is not a file, can't download\n");
    }

    print("Downloading " . $file->{"path"} . " to $localPath\n");

    // Ok its a file, grab its parts
    $fh = fopen($localPath, "a+b");
    foreach ($file->{"revisions"}[0]->{"parts"} as $part) {
        $data = $ca->getPart($part->{"fingerprint"}, $part->{"size"});
        fwrite($fh, $data);
    }
    fclose($fh);

    print("Successfully downloaded " . $file->{"path"} . " to $localPath\n");

    break;
}

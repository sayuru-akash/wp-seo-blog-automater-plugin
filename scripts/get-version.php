#!/usr/bin/env php
<?php

$rootDir = dirname(__DIR__);
$defaultFile = $rootDir . '/wp-seo-blog-automater.php';
$options = getopt('', array('file:'));
$targetFile = isset($options['file']) ? $options['file'] : $defaultFile;

if (!is_readable($targetFile)) {
    fwrite(STDERR, "Unable to read plugin file: {$targetFile}\n");
    exit(1);
}

$content = file_get_contents($targetFile);
if ($content === false) {
    fwrite(STDERR, "Unable to load plugin file: {$targetFile}\n");
    exit(1);
}

if (!preg_match('/^[ \t]*\*[ \t]+Version:[ \t]*([^\r\n]+)/m', $content, $headerMatch)) {
    fwrite(STDERR, "Unable to find plugin header version in {$targetFile}\n");
    exit(1);
}

if (!preg_match("/^[ \t]*define\\([ \t]*'WP_SEO_AUTOMATER_VERSION',[ \t]*'([^']+)'[ \t]*\\);/m", $content, $constantMatch)) {
    fwrite(STDERR, "Unable to find WP_SEO_AUTOMATER_VERSION in {$targetFile}\n");
    exit(1);
}

$headerVersion = trim($headerMatch[1]);
$constantVersion = trim($constantMatch[1]);
$versionPattern = '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/';

if ($headerVersion !== $constantVersion) {
    fwrite(
        STDERR,
        "Version mismatch in {$targetFile}: header={$headerVersion} constant={$constantVersion}\n"
    );
    exit(1);
}

if (!preg_match($versionPattern, $headerVersion)) {
    fwrite(STDERR, "Invalid semantic version in {$targetFile}: {$headerVersion}\n");
    exit(1);
}

fwrite(STDOUT, $headerVersion . PHP_EOL);

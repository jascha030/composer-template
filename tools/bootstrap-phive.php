<?php

declare(strict_types=1);

$toolsDir = __DIR__;
$phivePhar = $toolsDir . '/phive';

if (!is_file($phivePhar)) {
    if (!is_dir($toolsDir) && !mkdir($toolsDir, 0o755, true)) {
        fwrite(STDERR, "Failed to create {$toolsDir}\n");
        exit(1);
    }

    $url = 'https://phar.io/releases/phive.phar';
    $contents = @file_get_contents($url);

    if ($contents === false && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $contents = curl_exec($ch);
        curl_close($ch);
    }

    if ($contents === false) {
        fwrite(STDERR, "Failed to download phive from {$url}\n");
        exit(1);
    }

    if (file_put_contents($phivePhar, $contents) === false) {
        fwrite(STDERR, "Failed to write {$phivePhar}\n");
        exit(1);
    }

    chmod($phivePhar, 0o755);
}

array_shift($argv);
$args = array_map('escapeshellarg', $argv);
$cmd = escapeshellarg($phivePhar) . ($args !== [] ? ' ' . implode(' ', $args) : '');

passthru($cmd, $exitCode);
exit($exitCode);

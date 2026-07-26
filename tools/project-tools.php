<?php

declare(strict_types=1);

function runPhive(): void
{
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

    global $argv;
    array_shift($argv); // script name
    array_shift($argv); // 'phive' command
    $args = array_map('escapeshellarg', $argv);
    $cmd = escapeshellarg($phivePhar) . ($args !== [] ? ' ' . implode(' ', $args) : '');

    passthru($cmd, $exitCode);
    exit($exitCode);
}

function runPhpactorSchema(): void
{
    $phpactor = shell_exec('which phpactor 2>/dev/null');

    if (false === $phpactor || null === $phpactor || '' === trim($phpactor)) {
        fwrite(STDOUT, "phpactor not found, skipping schema generation.\n");
        exit(0);
    }

    passthru('phpactor config:json-schema phpactor.schema.json', $exitCode);
    exit($exitCode);
}

function showHelp(): void
{
    fwrite(STDOUT, "Usage: php tools/project-tools.php <command> [args...]\n\nCommands:\n  phive           Auto-download phive and proxy arguments through\n  phpactor:schema Generate phpactor.schema.json (requires global phpactor)\n");
    exit(1);
}

$command = $argv[1] ?? null;

match ($command) {
    'phive' => runPhive(),
    'phpactor:schema' => runPhpactorSchema(),
    default => showHelp(),
};

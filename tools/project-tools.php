<?php

declare(strict_types=1);

const PHIVE_VERSION = '0.16.0';
const PHIVE_DOWNLOAD_URL = 'https://github.com/phar-io/phive/releases/download/0.16.0/phive-0.16.0.phar';
const PHIVE_SHA256 = '1525f25afec4bcdc0aa8db7bb4b0063851332e916698daf90c747461642a42ed';
const PHIVE_GPG_KEY_ID = '6AF725270AB81E04D79442549D8A98B29B2D5D79';

function resolve_phive_binary(): string
{
    $toolsDir = __DIR__;
    $localPhive = $toolsDir . '/phive';

    if (is_file($localPhive)) {
        return $localPhive;
    }

    $globalPhive = shell_exec('which phive 2>/dev/null');
    if ($globalPhive !== false && $globalPhive !== null && '' !== trim($globalPhive)) {
        return trim($globalPhive);
    }

    return download_and_verify_phive($localPhive);
}

function download_and_verify_phive(string $targetPath): string
{
    $toolsDir = dirname($targetPath);
    if (!is_dir($toolsDir) && !mkdir($toolsDir, 0o755, true)) {
        fwrite(STDERR, "Failed to create {$toolsDir}\n");
        exit(1);
    }

    $contents = download_url(PHIVE_DOWNLOAD_URL);
    if ($contents === null) {
        fwrite(STDERR, "Failed to download phive from " . PHIVE_DOWNLOAD_URL . "\n");
        exit(1);
    }

    $gpgAvailable = shell_exec('which gpg 2>/dev/null');
    if ($gpgAvailable !== false && $gpgAvailable !== null && '' !== trim($gpgAvailable)) {
        verify_phive_with_gpg($contents);
    } else {
        verify_phive_with_sha256($contents);
    }

    if (file_put_contents($targetPath, $contents) === false) {
        fwrite(STDERR, "Failed to write {$targetPath}\n");
        exit(1);
    }

    chmod($targetPath, 0o755);

    return $targetPath;
}

function verify_phive_with_gpg(string $pharContents): void
{
    $ascUrl = PHIVE_DOWNLOAD_URL . '.asc';
    $ascContents = download_url($ascUrl);
    if ($ascContents === null) {
        fwrite(STDERR, "Failed to download GPG signature from {$ascUrl}\n");
        fwrite(STDERR, "GPG is available but signature download failed. Aborting for security.\n");
        exit(1);
    }

    $tmpPhar = tempnam(sys_get_temp_dir(), 'phive_phar_');
    $tmpAsc  = $tmpPhar . '.asc';
    file_put_contents($tmpPhar, $pharContents);
    file_put_contents($tmpAsc, $ascContents);

    passthru("gpg --keyserver hkps://keys.openpgp.org --recv-keys " . PHIVE_GPG_KEY_ID . " 2>/dev/null", $recvExit);
    passthru("gpg --verify " . escapeshellarg($tmpAsc) . " " . escapeshellarg($tmpPhar) . " 2>&1", $verifyExit);

    unlink($tmpPhar);
    unlink($tmpAsc);

    if ($verifyExit !== 0) {
        fwrite(STDERR, "GPG signature verification failed. The downloaded phive PHAR may be tampered.\n");
        exit(1);
    }
}

function verify_phive_with_sha256(string $pharContents): void
{
    if (hash('sha256', $pharContents) !== PHIVE_SHA256) {
        fwrite(STDERR, "SHA-256 mismatch: downloaded phive is corrupt or tampered.\n");
        fwrite(STDERR, "Expected:    " . PHIVE_SHA256 . "\n");
        fwrite(STDERR, "Calculated:  " . hash('sha256', $pharContents) . "\n");
        exit(1);
    }
}

function download_url(string $url): ?string
{
    $contents = @file_get_contents($url);

    if ($contents === false && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $contents = curl_exec($ch);
        curl_close($ch);
    }

    return $contents !== false ? $contents : null;
}

function proxy_to_phive(string $phivePath): void
{
    global $argv;
    array_shift($argv); // script name
    array_shift($argv); // 'phive' command
    $args = array_map('escapeshellarg', $argv);
    $cmd = escapeshellarg($phivePath) . ($args !== [] ? ' ' . implode(' ', $args) : '');

    passthru($cmd, $exitCode);
    exit($exitCode);
}

function generate_phpactor_json_schema_when_available(): void
{
    $phpactor = shell_exec('which phpactor 2>/dev/null');

    if (false === $phpactor || null === $phpactor || '' === trim($phpactor)) {
        fwrite(STDOUT, "phpactor not found, skipping schema generation.\n");
        exit(0);
    }

    passthru('phpactor config:json-schema phpactor.schema.json', $exitCode);
    exit($exitCode);
}

function print_usage_and_exit(): void
{
    fwrite(STDOUT, "Usage: php tools/project-tools.php <command> [args...]\n\nCommands:\n  phive           Auto-download phive and proxy arguments through\n  phpactor:schema Generate phpactor.schema.json (requires global phpactor)\n");
    exit(1);
}

$command = $argv[1] ?? null;

match ($command) {
    'phive' => proxy_to_phive(resolve_phive_binary()),
    'phpactor:schema' => generate_phpactor_json_schema_when_available(),
    default => print_usage_and_exit(),
};

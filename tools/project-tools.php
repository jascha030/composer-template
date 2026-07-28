<?php

/*
 * This file is part of the jascha030/template package.
 *
 * (c) Jascha van Aalst <contact@jaschavanaalst.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

const PHIVE_VERSION      = '0.16.0';
const PHIVE_DOWNLOAD_URL = 'https://github.com/phar-io/phive/releases/download/0.16.0/phive-0.16.0.phar';
const PHIVE_SHA256       = '1525f25afec4bcdc0aa8db7bb4b0063851332e916698daf90c747461642a42ed';
const PHIVE_GPG_KEY_ID   = '6AF725270AB81E04D79442549D8A98B29B2D5D79';

function resolve_phive_binary(): string
{
    $local = __DIR__ . '/phive';

    if (is_file($local)) {
        return $local;
    }

    $global = find_command('phive');

    if (null !== $global) {
        return $global;
    }

    return download_and_verify_phive($local);
}

function download_and_verify_phive(string $targetPath): string
{
    $directory = dirname($targetPath);

    if (! is_dir($directory) && ! mkdir($directory, 0o755, true)) {
        throw new RuntimeException(sprintf('Failed to create directory "%s".', $directory));
    }

    $contents = download_url(PHIVE_DOWNLOAD_URL);

    if (command_exists('gpg')) {
        assert_phive_gpg_signature_valid($contents);
    } else {
        assert_phive_sha256_matches($contents);
    }

    if (false === file_put_contents($targetPath, $contents)) {
        throw new RuntimeException(sprintf('Failed to write "%s".', $targetPath));
    }

    chmod($targetPath, 0o755);

    return $targetPath;
}

function assert_phive_gpg_signature_valid(string $pharContents): void
{
    $signature = download_url(PHIVE_DOWNLOAD_URL . '.asc');

    $temporaryPhar = tempnam(sys_get_temp_dir(), 'phive_');

    if (false === $temporaryPhar) {
        throw new RuntimeException('Unable to create temporary file.');
    }

    $temporarySignature = $temporaryPhar . '.asc';

    file_put_contents($temporaryPhar, $pharContents);
    file_put_contents($temporarySignature, $signature);

    try {
        passthru(
            sprintf(
                'gpg --keyserver hkps://keys.openpgp.org --recv-keys %s >/dev/null 2>&1',
                escapeshellarg(PHIVE_GPG_KEY_ID)
            ),
            $ignoredExitCode,
        );

        passthru(
            sprintf(
                'gpg --verify %s %s >/dev/null 2>&1',
                escapeshellarg($temporarySignature),
                escapeshellarg($temporaryPhar),
            ),
            $verificationExitCode,
        );
    } finally {
        @unlink($temporaryPhar);
        @unlink($temporarySignature);
    }

    if (0 !== $verificationExitCode) {
        throw new RuntimeException('GPG signature verification failed. The downloaded PHAR may have been tampered with.');
    }
}

function assert_phive_sha256_matches(string $contents): void
{
    $actual = hash('sha256', $contents);

    if (PHIVE_SHA256 !== $actual) {
        throw new RuntimeException(sprintf("SHA-256 verification failed.\nExpected:   %s\nCalculated: %s", PHIVE_SHA256, $actual));
    }
}

function download_url(string $url): string
{
    $contents = @file_get_contents($url);

    if (false === $contents && function_exists('curl_init')) {
        $curl = curl_init($url);

        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, \CURLOPT_FOLLOWLOCATION, true);

        $contents = curl_exec($curl);

        curl_close($curl);
    }

    if (! is_string($contents)) {
        throw new RuntimeException(sprintf('Failed to download "%s".', $url));
    }

    return $contents;
}

function command_exists(string $command): bool
{
    return null !== find_command($command);
}

function find_command(string $command): ?string
{
    $result = shell_exec(
        sprintf(
            'command -v %s 2>/dev/null',
            escapeshellarg($command),
        )
    );

    if (! is_string($result)) {
        return null;
    }

    $result = trim($result);

    return '' === $result ? null : $result;
}

/**
 * @param list<string> $argv
 */
function proxy_to_phive(string $phivePath, array $argv): never
{
    $arguments = array_slice($argv, 2);

    $command = escapeshellarg($phivePath);

    if ([] !== $arguments) {
        $command .= ' ' . implode(
            ' ',
            array_map('escapeshellarg', $arguments),
        );
    }

    passthru($command, $exitCode);

    exit($exitCode);
}

function generate_phpactor_json_schema_when_available(): never
{
    if (! command_exists('phpactor')) {
        fwrite(\STDOUT, "phpactor not found, skipping schema generation.\n");

        exit(0);
    }

    passthru(
        'phpactor config:json-schema phpactor.schema.json',
        $exitCode,
    );

    exit($exitCode);
}

function print_usage_and_exit(): never
{
    fwrite(
        \STDOUT,
        <<<'TEXT'
            Usage:
              php tools/project-tools.php <command> [args...]

            Commands:
              phive
                  Download PHIVE if necessary and proxy all arguments.

              phpactor:schema
                  Generate phpactor.schema.json.

            TEXT
    );

    exit(1);
}

if (\PHP_SAPI !== 'cli') {
    throw new RuntimeException('This script must be run from the command line.');
}

/**
 * @var list<string> $arguments
 */
$arguments = $_SERVER['argv'] ?? [];

try {
    match ($arguments[1] ?? null) {
        'phive'           => proxy_to_phive(resolve_phive_binary(), $arguments),
        'phpactor:schema' => generate_phpactor_json_schema_when_available(),
        default           => print_usage_and_exit(),
    };
} catch (Throwable $exception) {
    fwrite(\STDERR, $exception->getMessage() . \PHP_EOL);

    exit(1);
}

<?php

declare(strict_types=1);

$phpactor = shell_exec('which phpactor 2>/dev/null');

if (false === $phpactor || null === $phpactor || '' === trim($phpactor)) {
    fwrite(STDOUT, "phpactor not found, skipping schema generation.\n");
    exit(0);
}

passthru('phpactor config:json-schema phpactor.schema.json', $exitCode);
exit($exitCode);

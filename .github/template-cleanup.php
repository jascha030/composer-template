<?php

declare(strict_types=1);

$repository = getenv('GITHUB_REPOSITORY');

if (false === $repository || !\str_contains($repository, '/')) {
    \fwrite(\STDERR, "GITHUB_REPOSITORY not available.\n");
    exit(1);
}

[$owner, $repo] = \explode('/', $repository, 2);

$composerName = \strtolower("{$owner}/{$repo}");

function deriveNamespaceSegment(string $segment): string
{
    $segment = \preg_replace('/[^a-zA-Z0-9._-]/', '', $segment);
    $segment = \str_replace(['.', '-', '_'], ' ', \strtolower((string) $segment));
    $segment = \ucwords($segment);
    $segment = \str_replace(' ', '', $segment);

    if (\preg_match('/^[0-9]/', $segment)) {
        $segment = '_' . $segment;
    }

    return $segment;
}

$vendorNamespace = deriveNamespaceSegment($owner);
$packageNamespace = deriveNamespaceSegment($repo);
$namespace = "{$vendorNamespace}\\\\{$packageNamespace}\\\\";
$namespaceSource = "{$vendorNamespace}\\{$packageNamespace}";

$replacements = [
    'composer.json' => [
        '"name": "jascha030/template"' => '"name": "' . $composerName . '"',
        '"Jascha030\\\\Project\\\\"' => '"' . $namespace . '"',
        '"homepage": "https://github.com/jascha030"' => '"homepage": "https://github.com/' . $owner . '"',
    ],
    'README.md' => [
        'jascha030/composer-template' => $composerName,
        'https://github.com/jascha030/composer-template' => 'https://github.com/' . $owner . '/' . $repo,
    ],
    '.php-cs-fixer.dist.php' => [
        'jascha030/template' => $composerName,
    ],
    'src/Example.php' => [
        'jascha030/template' => $composerName,
        'Jascha030\\Project' => $namespaceSource,
    ],
    'tests/bootstrap.php' => [
        'jascha030/template' => $composerName,
    ],
    'tests/ExampleTest.php' => [
        'jascha030/template' => $composerName,
        'Jascha030\\Project' => $namespaceSource,
    ],
    '.github/CODEOWNERS' => [
        '@jascha030' => '@' . $owner,
    ],
    'AGENTS.md' => [
        'jascha030/template' => $composerName,
    ],
];

foreach ($replacements as $file => $map) {
    if (!\file_exists($file)) {
        continue;
    }

    $content = \file_get_contents($file);
    $newContent = \strtr($content, $map);

    if ($content !== $newContent) {
        \file_put_contents($file, $newContent);
        echo "Updated {$file}\n";
    }
}

echo "Template cleanup complete.\n";

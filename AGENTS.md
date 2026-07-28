# Agent Instructions

## What this is
A **template repository** for PHP Composer packages. It contains no application code — only scaffolding, tooling config, and a self-destructing GitHub Actions workflow that personalizes the repo on first push.

## PHP & Platform
- Requires **PHP 8.4+** (`composer.json` requires `php: >=8.4` and locks platform to `8.4`).
- `platform-check: false` is set in composer config.

## Installing dependencies
```bash
composer install
```
This triggers `post-install-cmd` which runs `@tools:install`.

## Dev tools & architecture

### Tool isolation strategy
This repo uses **multiple isolation strategies** — do not assume everything is in `vendor/`:

| Tool | Location | How it gets there |
|------|----------|-------------------|
| php-cs-fixer | `vendor-bin/php-cs-fixer/vendor/` | `bamarni/composer-bin-plugin` (isolated composer.json) |
| phpstan | `vendor/bin/phpstan` | Main `require-dev` in root composer.json |
| phpunit | `tools/phpunit.phar` | **phive** (`.phive/phars.xml`) — phive itself is resolved by `tools/project-tools.php` (local → global → auto-download) |

### phive bootstrap security
`tools/project-tools.php` resolves phive in this order:
1. `./tools/phive` (already downloaded)
2. `phive` on PATH (globally installed)
3. Auto-download phive 0.16.0 from GitHub releases with **GPG signature verification** if `gpg` is available, otherwise **SHA-256 checksum verification**.

If GPG verification is available but fails, the script aborts (no silent downgrade). The SHA-256 fallback protects against network corruption and basic MITM; GPG provides full authenticity. Pinned to version 0.16.0 — bump the constants in `tools/project-tools.php` when upgrading.

### php-cs-fixer quirk
The fixer config (`.php-cs-fixer.dist.php`) requires `vendor-bin/php-cs-fixer/vendor/autoload.php` to load a custom config class from `jascha030/php-cs-fixer-config`. If that autoload file is missing, the fixer won't run.

### phpstan quirk
`phpstan.neon.dist` bootstraps `vendor-bin/php-cs-fixer/vendor/autoload.php` — this is intentional because phpstan needs to understand the custom php-cs-fixer config classes. The config also analyzes `tools/`, so `project-tools.php` is checked at level max.

## Running checks

```bash
# Run tests (installs tools, runs phpunit with coverage)
composer run test

# Run static analysis
composer run analyze

# Format code
composer run format

# Regenerate phpstan baseline
composer run analyze:baseline

# Install/update phive tools only
composer run tools:install
```

## Testing
- PHPUnit config: `phpunit.xml.dist`
- Bootstrap: `tests/bootstrap.php`
- Coverage output: `.var/cache/phpunit/cov.xml`
- Fixtures directory: `tests/Fixtures/`
- `requireCoverageMetadata="true"` is set — tests without coverage metadata will fail.

## Template automation

### `.github/workflows/template-cleanup.yml`
Self-destructs on first push to `main`/`master` (only runs when `github.run_number == 1`):
1. Runs `.github/template-cleanup.php`
2. Commits the customized files
3. Deletes itself and the script
4. Commits again and pushes

### `.github/template-cleanup.php`
Derives replacements from `GITHUB_REPOSITORY` env var (`owner/repo`):
- Composer package name → `owner/repo`
- PSR-4 namespace → `Owner\Repo\` (sanitized, ucwords)
- README install commands, GitHub links, CODEOWNERS, etc.

**To test locally:**
```bash
GITHUB_REPOSITORY="myuser/my-lib" php .github/template-cleanup.php
# Reset after testing:
git checkout -- composer.json README.md .php-cs-fixer.dist.php src/Example.php tests/bootstrap.php tests/ExampleTest.php .github/CODEOWNERS AGENTS.md
```

## Editor / IDE
- `.phpactor.json` points tools to `%project_root%/` paths and includes a `$schema` reference to `phpactor.schema.json` for LSP autocomplete.
- `.editorconfig`: 2 spaces for general files, **4 spaces** for PHP, `composer.json`, and XML.

### Phpactor schema
`phpactor.schema.json` is committed to the repo so the JSON LSP (e.g. neovim) provides autocomplete for `.phpactor.json` out of the box.
To regenerate it after a phpactor upgrade: `composer run phpactor:schema` (requires phpactor installed globally; silently skipped otherwise).

## State of the repo
- `src/` contains `Example.php` — a placeholder class so the CI suite can run out of the box.
- `tests/` contains `bootstrap.php`, `ExampleTest.php`, and `Fixtures/.gitkeep`.
- These placeholders are intentionally minimal; replace them with your package's actual classes and tests.

## What NOT to change without thought
- Do not move phpstan into `vendor-bin/` — the root `require-dev` and `phpstan.neon.dist` are wired together.
- Do not change the `autoload`/`autoload-dev` namespaces without also updating the template-cleanup script.
- Do not remove `.github/template-cleanup.php` or the workflow without an alternative templating strategy.

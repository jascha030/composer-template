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
| phpunit | `tools/phpunit.phar` | **phive** (`.phive/phars.xml`) — phive itself is auto-downloaded by `tools/bootstrap-phive.php` on first use |

### php-cs-fixer quirk
The fixer config (`.php-cs-fixer.dist.php`) requires `vendor-bin/php-cs-fixer/vendor/autoload.php` to load a custom config class from `jascha030/php-cs-fixer-config`. If that autoload file is missing, the fixer won't run.

### phpstan quirk
`phpstan.neon.dist` bootstraps `vendor-bin/php-cs-fixer/vendor/autoload.php` — this is intentional because phpstan needs to understand the custom php-cs-fixer config classes.

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
git checkout -- composer.json README.md .php-cs-fixer.dist.php tests/bootstrap.php .github/CODEOWNERS AGENTS.md
```

## Editor / IDE
- `.phpactor.json` points tools to `%project_root%/` paths and includes a `$schema` reference to `phpactor.schema.json` for LSP autocomplete.
- `.editorconfig`: 2 spaces for general files, **4 spaces** for PHP, `composer.json`, and XML.

### Phpactor schema
`phpactor.schema.json` is committed to the repo so the JSON LSP (e.g. neovim) provides autocomplete for `.phpactor.json` out of the box.
To regenerate it after a phpactor upgrade: `composer run phpactor:schema` (requires phpactor installed globally; silently skipped otherwise).

## State of the repo
- `src/` contains only `.gitkeep` — no classes yet.
- `tests/` contains only `bootstrap.php` and `Fixtures/.gitkeep`.
- This is expected; it is a skeleton.

## What NOT to change without thought
- Do not move phpstan into `vendor-bin/` — the root `require-dev` and `phpstan.neon.dist` are wired together.
- Do not change the `autoload`/`autoload-dev` namespaces without also updating the template-cleanup script.
- Do not remove `.github/template-cleanup.php` or the workflow without an alternative templating strategy.

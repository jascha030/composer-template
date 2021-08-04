# Composer project template

Description: A simple project description similar to the one found in your `composer.json`.

## Getting started

## Prerequisites

* Php `^7.4 || ^8.0`
* Composer `^2`

> **Note: Don't forget to include any server prerequisites**, for example _php extensions_,
> even when they are extensions which are enabled by default, you can save people a lot of wasted time in the off-chance
> that one of these prerequisites is not available to their environment. (**e.g.** `php-json`)

### Installation

Installation instructions, with _examples like the one below_.

```shell
composer require jascha030/composer-template
```

#### Distribution

Alternative steps for distribution.

```shell
composer require --no-dev jascha030/composer-template
```

## Usage

Extensive instructions in how to use your package in general or for use in the development of other projects.

### Testing

Included with the package are a set of Unit tests using `phpunit/phpunit`. For ease of use a composer script command is
defined to run the tests.

```shell
composer run phpunit
```

A code coverage report is generated in the project's root as `cov.xml`. The `cov.xml` file is not ignored in the
`.gitignore` by default. You are encouraged to commit the latest code coverage report, when deploying new features.

### Code style & Formatting

A code style configuration for `friendsofphp/php-cs-fixer` is included, defined in `.php-cs-fixer.dist.php`. By default,
it includes the `PSR-1` and `PSR-12` presets. You can customize or add rules in `.php-cs-fixer.dist.php`.

To use php-cs-fixer without having it necessarily installed globally, a composer script command is also included to
format php code using the provided config file and the vendor binary of php-cs-fixer.

```shell
composer run format
```

> **Note:** [https://mlocati.github.io/php-cs-fixer-configurator/#version:3.0](https://mlocati.github.io/php-cs-fixer-configurator/#version:3.0)
is a nifty tool to compose and export a custom code style configuration, I encourage anyone interested to use this tool.

## Development and contribution

Instructions regarding further contribution to the package itself.

## Inspiration and appreciation

Any developer should take and learn from other open-source projects. But I encourage everyone to state their sources and
inspirations. If it's not to promote a healthy community, it might inspire or teach others.

Also I like to add some general information about why I made something or just about the general subject. And if I can
think of any, I like to leave some links to resources or articles from other developers who might have taught me in
understanding a certain subject.

## License

This composer package is an open-sourced software licensed under
the [MIT License](https://github.com/jascha030/composer-template/blob/master/LICENSE.md)

> **Note:** to find the right license for your project
> use Github's [https://choosealicense.com/](https://choosealicense.com/),
> or read up on any other information, regarding Licensing your project in [their docs page on licensing](https://docs.github.com/en/github/creating-cloning-and-archiving-repositories/creating-a-repository-on-github/licensing-a-repository). 

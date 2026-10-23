CleverAge/ArchiveProcessBundle
==============================

[![Test](https://github.com/cleverage/archive-process-bundle/actions/workflows/test.yml/badge.svg)](https://github.com/cleverage/archive-process-bundle/actions/workflows/test.yml)
[![Packagist Version](https://img.shields.io/packagist/v/cleverage/archive-process-bundle)](https://packagist.org/packages/cleverage/archive-process-bundle)
[![License](https://img.shields.io/packagist/l/cleverage/archive-process-bundle)](LICENSE)

This bundle is a part of the [CleverAge/ProcessBundle](https://github.com/cleverage/process-bundle) project.
It provides Archive integration on Process bundle.
Compatible with [Symfony stable version and latest Long-Term Support (LTS) release](https://symfony.com/releases).

## Installation

```bash
composer require cleverage/archive-process-bundle
```

Without Symfony Flex, register the bundle in `config/bundles.php`:

```php
CleverAge\ArchiveProcessBundle\CleverAgeArchiveProcessBundle::class => ['all' => true],
```

## Documentation

For usage documentation, see:
[docs/index.md](docs/index.md)

## Support & Contribution

For general support and questions, please use [Github](https://github.com/cleverage/archive-process-bundle/issues).
If you think you found a bug or you have a feature idea to propose, feel free to open an issue after looking at the [contributing](CONTRIBUTING.md) guide.

## License

This bundle is under the MIT license.
For the whole copyright, see the [LICENSE](LICENSE) file distributed with this source code.

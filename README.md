# Laravel Likeable

[![Latest Version on Packagist](https://img.shields.io/packagist/v/akira/laravel-likeable.svg)](https://packagist.org/packages/akira/laravel-likeable)
[![Total Downloads](https://img.shields.io/packagist/dt/akira/laravel-likeable.svg)](https://packagist.org/packages/akira/laravel-likeable)
[![PHPStan Level](https://img.shields.io/badge/phpstan-level%209-brightgreen.svg)](https://phpstan.org)
[![License](https://img.shields.io/packagist/l/akira/laravel-likeable.svg)](https://github.com/akira-io/laravel-likeable/blob/main/LICENSE)

**Laravel Likeable** is a lightweight and flexible package that seamlessly adds like and unlike functionality to your
Eloquent models.

## Installation

You can install the package via composer:

```bash
composer require akira/laravel-likeable
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="likeable-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="likeable-config"
```

## Documentation

You'll find installation instructions and full documentation on [Followable website](https://likeable.akira-io.com).

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [kidiatoliny](https://github.com/kidiatoliny)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
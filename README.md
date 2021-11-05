![The word Sey on a blue background](art/logo.svg)

# Sey

[![Tests](https://github.com/felixdorn/sey/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/felixdorn/sey/actions/workflows/tests.yml)
[![Formats](https://github.com/felixdorn/sey/actions/workflows/formats.yml/badge.svg?branch=main)](https://github.com/felixdorn/sey/actions/workflows/formats.yml)
[![Version](https://poser.pugx.org/felixdorn/sey/version)](//packagist.org/packages/felixdorn/sey)
[![Total Downloads](https://poser.pugx.org/felixdorn/sey/downloads)](//packagist.org/packages/felixdorn/sey)
[![License](https://poser.pugx.org/felixdorn/sey/license)](//packagist.org/packages/felixdorn/sey)

Sey, pronounce say, is a powerful math interpreter with infinite-precision.

## Installation

> Requires [PHP 8.0.0+](https://php.net/releases) and the `bcmath` extension

You can install the package via composer:

```bash
composer require felixdorn/sey
```

## Usage

### Floating Point Precision

By default, the precision is 16 which is roughly equal to the PHP default (even though it is platform-dependent, it's
very common).

You may change it:

```php
\Felix\Sey\Sey:precision(32)
```

Under the hood, it just calls `bcscale` for the calculation and then rollbacks to the previous value as to not create
side effects.

## Syntax

It's just math.

```
1 + 2
2 - 3
3 * 4
4 / 5
5 % 6
6 ^ 7
7 * (8 + 9)
sqrt(10)
powmod(11)
11(12 - 13)
(14 + 15)^16
!(5)
pi()
```

### Variables

You can not define variables in your code but you can pass them at compile-time.

```php
sey('2 * r * pi', [
    'r' => 10,
    'pi' => 3.1415
])
```

### Functions

* `sqrt`: `bcsqrt`
* `powmod`: `bcpowmod`
* `pi()`: custom `bcpi` function

  This function returns pi with your defined precision up to 999 digits.

* `!(n)`: custom `bcfact` function

  This computes `n!`.

#### Custom functions

They can override built-ins so you can redefine `!` to use a lookup table for example.

```php
Sey::define('fact', function (int $n, /* as many arguments as you want */) {
    return $factorials[$n] ?? bcfact($n);
});
```

```bash
composer test
```

**sey** was created by **[Félix Dorn](https://twitter.com/afelixdorn)** under
the **[MIT license](https://opensource.org/licenses/MIT)**.

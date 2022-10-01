![The word Sey on a blue background](art/logo.svg)

# Sey

[![Tests](https://github.com/felixdorn/sey/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/felixdorn/sey/actions/workflows/tests.yml)
[![Formats](https://github.com/felixdorn/sey/actions/workflows/formats.yml/badge.svg?branch=main)](https://github.com/felixdorn/sey/actions/workflows/formats.yml)
[![Version](https://poser.pugx.org/felixdorn/sey/version)](//packagist.org/packages/felixdorn/sey)
[![Total Downloads](https://poser.pugx.org/felixdorn/sey/downloads)](//packagist.org/packages/felixdorn/sey)
[![License](https://poser.pugx.org/felixdorn/sey/license)](//packagist.org/packages/felixdorn/sey)

Sey, pronounce say, is a powerful math interpreter with arbitrary-precision.

## Installation

> Requires [PHP 8.0.0+](https://php.net/releases) and the `bcmath` extension

You can install the package via composer:

```bash
composer require felixdorn/sey
```

## Usage

```php
Sey::parse('(0.5 + 0.5) / 3)'); // 0.3333333333333333
// or
sey('a / b', ['a' => 1, 'b' => 2]); // 0.5
```

### Precision

By default, the maximum floating precision is 16.

You may change it:

```php
\Felix\Sey\Sey:precision(32);
```

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
Sey::parse('2 * r * pi', [
    'r' => 10,
    'pi' => 3.1415
]);
```

### Functions

* `sqrt`: `bcsqrt`
* `powmod`: `bcpowmod`
* `pi()`: custom `bcpi` function

  This function returns pi with your defined precision up to 999 digits.

* `!(n)`: custom `bcfact` function

  This computes `n!`, if you need to do it really quickly, you should probably use a lookup table.

#### Custom functions

> You can override built-ins functions.

```php
Sey::define('!', function (int $n, /* as many arguments as you want */) {
    return $factorials[$n] ?? bcfact($n);
});
```

The function name must match the following regex `[a-z_A-Z!]+[a-z_A-Z0-9]*`.

So, first character must be a letter or ! followed by any number of letters or numbers.

### Tests

```bash
composer test
```

**sey** was created by **[Félix Dorn](https://twitter.com/afelixdorn)** under
the **[MIT license](https://opensource.org/licenses/MIT)**.

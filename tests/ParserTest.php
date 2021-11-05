<?php

use Felix\BcExpr\Sey;

it('returns zero with an empty expression', function () {
    $output = Sey::parse('');

    expect($output)->toBe('0');
});

it('can bind variables in an expression', function () {
    $output = Sey::parse('a + b', [
        'a' => 1,
        'b' => 2,
    ]);

    expect($output)->toBe('3.0000000000000000');
});

it('does operations in the right order', function () {
    $output = Sey::parse('1 + 2 * 5');
    expect($output)->toBe('11.0000000000000000');
});

it('does operations in parentheses first', function () {
    $output = Sey::parse('10 * (5 + 2)');
    expect($output)->toBe('70.0000000000000000');
});

it('can define and call functions', function () {
    Sey::define('seven', function ($delta) {
        return 7 + $delta;
    });

    $output = Sey::parse('seven(1)');
    expect($output)->toBe('8');

    $output = Sey::parse('1 + seven(1)');
    expect($output)->toBe('9.0000000000000000');
});

it('can define an arbitrary precision', function () {
    $output = Sey::parse('1 / 3');
    expect($output)->toBe('0.' . str_repeat('3', Sey::precision()));

    Sey::precision(3);

    $output = Sey::parse('1 / 1000');
    expect($output)->toBe('0.001');
});

it('can compute a factorial', function () {
    $output = Sey::parse('!(5)');
    expect($output)->toBe(bcfact('5'));
});

it('can compute pi', function () {
    $output = Sey::parse('pi()');
    expect($output)->toBe(bcpi());
});

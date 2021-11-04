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

    expect($output)->toBe('3');
});

it('does operations in the right order', function () {
    $output = Sey::parse('1 + 2 * 5');
    expect($output)->toBe('11');
});

it('does operations in parentheses first', function () {
    $output = Sey::parse('10 * (5 + 2)');
    expect($output)->toBe('70');
});

it('can define and call functions', function () {
    Sey::define('seven', function ($delta) {
        return 7 + $delta;
    });

    $output = Sey::parse('seven(1)');
    expect($output)->toBe('8');
});

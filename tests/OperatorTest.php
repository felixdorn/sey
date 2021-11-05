<?php

use Felix\Sey\Sey;
use Felix\Sey\Tokens\Number;
use Felix\Sey\Tokens\Operator;

it('can evaluate correctly an expression', function (string $bcFunction, string $operator, string $left, string $right, string $result) {
    $op = new Operator($operator);

    expect($op->evaluate(
        new Number($left),
        new Number($right),
        Sey::precision()
    ))->toBe($bcFunction($left, $right, Sey::precision()));
})->with([
    ['bcadd', '+', '1', '2', '3'],
    ['bcsub', '-', '1', '2', '-1'],
    ['bcmul', '*', '1', '2', '2'],
    ['bcdiv', '/', '1', '2', '0.5'],
    ['bcmod', '%', '10', '2', '0'],
    ['bcpow', '^', '2', '4', '32'],
]);

it('can correctly evaluate an operator', function (string $op, bool $isValid) {
    expect(Operator::isValid($op))->toBe($isValid);
})->with([
    ['+', true],
    ['-', true],
    ['*', true],
    ['/', true],
    ['%', true],
    ['^', true],
    ['@', false],
    ['4', false],
]);

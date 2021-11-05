<?php

use Felix\Sey\Sey;

it('can parse using the sey function', function () {
    expect(sey('1 / 3'))->toBe(Sey::parse('1 / 3'));
});

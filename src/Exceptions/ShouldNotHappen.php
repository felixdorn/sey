<?php

namespace Felix\Sey\Exceptions;

use Exception;

/**
 * @internal
 */
class ShouldNotHappen extends Exception
{
    public function __construct(string $message, string|int|float ...$formats)
    {
        parent::__construct(
            sprintf($message, ...$formats)
        );
    }
}

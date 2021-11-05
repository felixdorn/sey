<?php

namespace Felix\Sey;

class RuntimeContext
{
    public array $queue     = [];
    public array $stack     = [];
    public int $stackLength = 0;
}

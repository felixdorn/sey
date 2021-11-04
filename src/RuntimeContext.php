<?php

namespace Felix\BcExpr;

class RuntimeContext
{
    public array $queue     = [];
    public array $stack     = [];
    public int $stackLength = 0;
}

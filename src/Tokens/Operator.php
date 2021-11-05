<?php

namespace Felix\Sey\Tokens;

use Felix\Sey\Contracts\Token;
use Felix\Sey\Exceptions\ShouldNotHappen;
use Felix\Sey\Runtime;

class Operator extends Token
{
    public static function isValid(string $value): bool
    {
        return match ($value) {
            '+', '-', '*', '/', '%', '^' => true,
            default => false
        };
    }

    public function evaluate(Number $left, Number $right, int $precision): string
    {
        $function = match ($this->value) {
            '+'     => 'bcadd',
            '-'     => 'bcsub',
            '*'     => 'bcmul',
            '/'     => 'bcdiv',
            '%'     => 'bcmod',
            '^'     => 'bcpow',
            default => throw new ShouldNotHappen('A string has been evaluated as a token but not properly implemented.')
        };

        return $function($left->value, $right->value, $precision);
    }

    public function consume(Runtime $runtime): void
    {
        // While there is an operator token, o2, at the top of the stack
        // op1 is left-associative and its precedence is less than or equal to that of op2,
        // or op1 has precedence less than that of op2,
        // Let + and ^ be right associative.
        // Correct transformation from 1^2+3 is 12^3+
        // The differing operator priority decides pop / push
        // If 2 operators have equal priority then associativity decides.
        while (!empty($this->stack)) {
            $comparison = $runtime->operatorStack->top();

            if ($comparison instanceof Operator) {
                if (!($this->associative() && $this->precedence() <= $comparison->precedence() || $this->precedence() < $comparison->precedence())) {
                    break;
                }

                // Pop comparison off the stack, onto the output queue;
                $runtime->queue->push(
                    $runtime->operatorStack->pop()
                );
            } else {
                break;
            }
        }

        // push op1 onto the stack.
        $runtime->operatorStack->push($this);
    }

    private function associative(): bool
    {
        return $this->value === '^';
    }

    public function precedence(): int
    {
        return match ($this->value) {
            '-' => 1,
            '+' => 2,
            '*', '/', '%' => 3,
            '^'     => 5,
            default => throw new ShouldNotHappen('A string has been evaluated as a token but not properly implemented.')
        };
    }
}

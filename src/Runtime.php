<?php

namespace Felix\Sey;

use Felix\Sey\Exceptions\SyntaxError;
use Felix\Sey\Tokens\CloseParenthesis;
use Felix\Sey\Tokens\Func;
use Felix\Sey\Tokens\Identifier;
use Felix\Sey\Tokens\Number;
use Felix\Sey\Tokens\OpenParenthesis;
use Felix\Sey\Tokens\Operator;
use SplStack;

class Runtime
{
    public Stack $tokens;
    public SplStack $operatorStack;
    public SplStack $queue;

    protected string $expression;
    protected array $variables;
    protected array $functions;
    protected int $precision;
    protected int $stackLength = 0;

    public function __construct(string $expression, array $variables, array $functions, int $precision)
    {
        $this->operatorStack = new SplStack();
        $this->queue         = new SplStack();
        $this->tokens        = Scanner::scan($expression);
        $this->expression    = $expression;
        $this->variables     = $variables;
        $this->functions     = $functions;
        $this->precision     = $precision;
    }

    public function run(): string
    {
        while ($this->tokens->current() !== null) {
            $this->tokens->current()->consume($this);
            $this->tokens->next();
        }

        // no more tokens to read but some operators are still in the stack
        while (!$this->operatorStack->isEmpty()) {
            $token = $this->operatorStack->pop();

            if ($token instanceof OpenParenthesis || $token instanceof CloseParenthesis) {
                throw SyntaxError::incorrectParenthesesNesting();
            }

            $this->queue->push($token);
        }

        // While there are input tokens left
        // Read the next token from input.
        while (!$this->queue->isEmpty()) {
            $token = $this->queue->shift();
            if ($token instanceof Identifier) {
                if (!array_key_exists($token->value, $this->variables)) {
                    throw SyntaxError::undefinedVariable($token);
                }

                $this->operatorStack->push(
                    new Number($this->variables[$token->value])
                );
                $this->stackLength++;
                continue;
            }

            if ($token instanceof Number) {
                // If the token is a number, then push it onto the stack.
                $this->operatorStack->push($token);
                $this->stackLength++;
                continue;
            }

            if ($token instanceof Func) {
                // function
                $argc = $token->argc;
                $argv = [];

                $this->stackLength -= $argc - 1;

                for (; $argc > 0; $argc--) {
                    array_unshift($argv, $this->operatorStack->pop()->value);
                }

                if (!array_key_exists($token->value, $this->functions)) {
                    throw SyntaxError::unexpectedToken($token->value);
                }

                // Push the returned results, if any, back onto the stack.
                $this->operatorStack->push(
                    new Number((string) ($this->functions[$token->value])(...$argv))
                );
                continue;
            }

            if ($token instanceof Operator) {
                // If there are fewer than 2 values on the stack
                if ($this->stackLength < 2) {
                    throw SyntaxError::missingParameters($token);
                }

                $right = $this->operatorStack->pop();
                $left  = $this->operatorStack->pop();
                $this->stackLength--;

                $this->operatorStack[] = new Number(
                    $token->evaluate($left, $right, $this->precision)
                );
                continue;
            }

            throw SyntaxError::unexpectedToken($token);
        }

        // That value is the result of the calculation.
        if ($this->operatorStack->count() === 0) {
            return '0';
        }

        return $this->operatorStack->pop()->value;
    }
}

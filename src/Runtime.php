<?php

namespace Felix\Sey;

use Felix\Sey\Exceptions\SyntaxError;

class Runtime
{
    protected string $expression;
    protected array $variables;
    protected array $functions;
    protected int $precision;
    protected Scanner $scanner;

    protected array $queue     = [];
    protected array $stack     = [];
    protected int $stackLength = 0;


    protected static array $precedence = [
        Token::T_POW   => 4,
        Token::T_TIMES => 3,
        Token::T_DIV   => 3,
        Token::T_MOD   => 3,
        Token::T_PLUS  => 2,
        Token::T_MINUS => 1,
    ];

    public function __construct(string $expression, array $variables, array $functions, int $precision)
    {
        $this->scanner    = Scanner::scan($expression);
        $this->expression = $expression;
        $this->variables  = $variables;
        $this->functions  = $functions;
        $this->precision  = $precision;
    }

    public function run()
    {
        while (($token = $this->scanner->next()) !== false) {
            $this->consumeToken($token, $this->scanner);
        }

        // no more tokens to read but some operators are still in the stack
        while ($token = array_pop($this->stack)) {
            if ($token->is(Token::T_OPEN_PARENTHESIS, Token::T_CLOSE_PARENTHESIS)) {
                throw SyntaxError::new('Incorrect nesting of parentheses');
            }

            $this->queue[] = $token;
        }

        // While there are input tokens left
        // Read the next token from input.
        while ($token = array_shift($this->queue)) {
            if ($token->is(Token::T_IDENTIFIER)) {
                if (!array_key_exists($token->value, $this->variables)) {
                    throw SyntaxError::new('Undefined variable %s', $token->value);
                }

                $this->stack[] = new Token(Token::T_NUMBER, $this->variables[$token->value]);
                $this->stackLength++;
                continue;
            }

            if ($token->is(Token::T_NUMBER)) {
                // If the token is a number, then push it onto the stack.
                $this->stack[] = $token;
                $this->stackLength++;
                continue;
            }

            if ($token->is(Token::T_FUNCTION)) {
                // function
                $argc = $token->argc;
                $argv = [];

                $this->stackLength -= $argc - 1;

                for (; $argc > 0; $argc--) {
                    array_unshift($argv, array_pop($this->stack)->value);
                }

                if (!array_key_exists($token->value, $this->functions)) {
                    throw SyntaxError::unexpectedToken($token->value);
                }

                // Push the returned results, if any, back onto the stack.
                $this->stack[] = new Token(
                    Token::T_NUMBER,
                    (string) ($this->functions[$token->value])(...$argv)
                );
                continue;
            }

            if ($token->isOperator()) {
                // If there are fewer than n values on the stack
                if ($this->stackLength < $token->argc) {
                    throw SyntaxError::missingParameters($token);
                }

                $rightHandSideOp = array_pop($this->stack);
                $leftHandSideOp  = array_pop($this->stack);
                $this->stackLength--;

                $this->stack[] = new Token(
                    Token::T_NUMBER,
                    $this->evaluateOperator($token, $leftHandSideOp, $rightHandSideOp)
                );
                continue;
            }

            throw SyntaxError::unexpectedToken($token);
        }

        // That value is the result of the calculation.
        return array_pop($this->stack)->value ?? '0';
    }

    protected function consumeToken(Token $token): void
    {
        $consumer = match ($token->type) {
            Token::T_PLUS, Token::T_MINUS, Token::T_TIMES, Token::T_DIV, Token::T_MOD, Token::T_POW => [$this, 'consumeOperator'],
            Token::T_NUMBER, Token::T_IDENTIFIER => function (Token $token) {
                $this->queue[] = $token;
            },
            Token::T_OPEN_PARENTHESIS => function (Token $token) {
                $this->stack[] = $token;
            },
            Token::T_FUNCTION          => [$this, 'consumeFunction'],
            Token::T_COMMA             => [$this, 'consumeComma'],
            Token::T_CLOSE_PARENTHESIS => [$this, 'consumeCloseParenthesis'],
            default                    => throw SyntaxError::unexpectedToken($token)
        };

        $consumer($token);
    }

    protected function evaluateOperator(Token $operator, Token $left, Token $right): string
    {
        $left  = $left->value;
        $right = $right->value;

        return match ($operator->type) {
            Token::T_PLUS  => bcadd($left, $right, $this->precision),
            Token::T_MINUS => bcsub($left, $right, $this->precision),
            Token::T_TIMES => bcmul($left, $right, $this->precision),
            Token::T_DIV   => bcdiv($left, $right, $this->precision),
            Token::T_MOD   => bcmod($left, $right, $this->precision),
            Token::T_POW   => bcpow($left, $right, $this->precision),
        };
    }

    private function consumeComma(): void
    {
        // If the token is a function argument separator (e.g., a comma):
        $pe = false;

        while ($token = end($this->stack)) {
            if ($token->type === Token::T_OPEN_PARENTHESIS) {
                $pe = true;
                break;
            }

            // Until the token at the top of the stack is a left parenthesis,
            // pop operators off the stack onto the output queue.
            $this->queue[] = array_pop($this->stack);
        }

        // If no left parentheses are encountered, either the separator was misplaced
        // or parentheses were mismatched.
        if ($pe !== true) {
            throw SyntaxError::new('Missing token `(` or misplaced token `,`');
        }
    }

    private function consumeFunction(Token $token): void
    {
        // If the token is a function token, then push it onto the stack.
        $this->stack[] = $token;
        $argc          = 0;
        $parenthesis   = 0;

        // we skip the (
        $this->consumeToken($this->scanner->next());

        if ($this->scanner->peek()) { // more tokens?
            while ($ahead = $this->scanner->next()) {
                $this->consumeToken($ahead);

                // nested parenthesis inside function calls
                if ($ahead->is(Token::T_OPEN_PARENTHESIS)) {
                    $parenthesis++;
                } elseif ($ahead->is(Token::T_CLOSE_PARENTHESIS) && $parenthesis-- === 0) {
                    break;
                }

                $argc = max($argc, 1); // at least 1 arg if bracket not closed immediately

                if ($ahead->is(Token::T_COMMA)) {
                    $argc++;
                }
            }
        }

        $token->argc = $argc;
    }

    private function consumeOperator(Token $token): void
    {
        while (!empty($this->stack)) {
            $s = end($this->stack);

            // While there is an operator token, o2, at the top of the stack
            // op1 is left-associative and its precedence is less than or equal to that of op2,
            // or op1 has precedence less than that of op2,
            // Let + and ^ be right associative.
            // Correct transformation from 1^2+3 is 12^3+
            // The differing operator priority decides pop / push
            // If 2 operators have equal priority then associativity decides.
            switch ($s->type) {
                default:
                    break 2;
                case Token::T_PLUS:
                case Token::T_MINUS:
                case Token::T_TIMES:
                case Token::T_DIV:
                case Token::T_MOD:
                case Token::T_POW:
                    $p1 = self::$precedence[$token->type];
                    $p2 = self::$precedence[$s->type];

                    if (!(($token->is(Token::T_POW) && ($p1 <= $p2)) || ($p1 < $p2))) {
                        break 2;
                    }

                    // Pop o2 off the stack, onto the output queue;
                    $this->queue[] = array_pop($this->stack);
            }
        }

        // push op1 onto the stack.
        $this->stack[] = $token;
    }

    private function consumeCloseParenthesis(): void
    {
        $pe = false;

        // Until the token at the top of the stack is a left parenthesis,
        // pop operators off the stack onto the output queue
        while ($token = array_pop($this->stack)) {
            if ($token->is(Token::T_OPEN_PARENTHESIS)) {
                // Pop the left parenthesis from the stack, but not onto the output queue.
                $pe = true;
                break;
            }

            $this->queue[] = $token;
        }

        // If the stack runs out without finding a left parenthesis, then there are mismatched parentheses.
        if ($pe === false) {
            throw SyntaxError::unexpectedToken(')');
        }

        // If the token at the top of the stack is a function token, pop it onto the output queue.
        if (($token = end($this->stack)) && $token->is(Token::T_FUNCTION)) {
            $this->queue[] = array_pop($this->stack);
        }
    }
}

<?php

namespace Felix\Sey;

class Sey
{
    protected static array $functions = [
        'sqrt'   => 'bcsqrt',
        'powmod' => 'bcpowmod',
        'pi'     => 'bcpi',
        '!'      => 'bcfact',
    ];

    protected static int $precision = 16;

    protected static array $precedence = [
        Token::T_POW   => 4,
        Token::T_TIMES => 3,
        Token::T_DIV   => 3,
        Token::T_MOD   => 3,
        Token::T_PLUS  => 2,
        Token::T_MINUS => 1,
    ];
    protected static array $operators = [
        Token::T_POW,
        Token::T_TIMES,
        Token::T_DIV,
        Token::T_MOD,
        Token::T_PLUS,
        Token::T_MINUS,
    ];

    public static function define(string $function, callable $definition): void
    {
        static::$functions[$function] = $definition;
    }

    public static function precision(?int $precision = null): int
    {
        if ($precision === null) {
            return static::$precision;
        }

        static::$precision = $precision;

        return $precision;
    }

    public static function parse(string $expression, array $variables = [], array $functions = []): string
    {
        $expression = trim($expression);

        if ($expression === '') {
            return '0';
        }

<<<<<<< HEAD
        $runtime = new Runtime(
            $expression,
            $variables,
            array_merge(static::$functions, $functions),
            static::$precision
        );
=======
        $tokenStream = TokenStream::create($expression);
        $context     = new RuntimeContext();

        while (($token = $tokenStream->next()) !== false) {
            self::consumeToken($context, $token, $tokenStream);
        }

        // no more tokens to read but some operators are still in the stack
        while ($token = array_pop($context->stack)) {
            if ($token->is(Token::T_OPEN_PARENTHESIS, Token::T_CLOSE_PARENTHESIS)) {
                throw SyntaxError::new('Incorrect nesting of parentheses');
            }

            $context->queue[] = $token;
        }

        // While there are input tokens left
        // Read the next token from input.
        while ($token = array_shift($context->queue)) {
            if ($token->is(Token::T_IDENTIFIER)) {
                if (!array_key_exists($token->value, $variables)) {
                    throw SyntaxError::new('Undefined variable %s', $token->value);
                }

                $context->stack[] = new Token(Token::T_NUMBER, $variables[$token->value]);
                $context->stackLength++;
                continue;
            }

            if ($token->is(Token::T_NUMBER)) {
                // If the token is a number, then push it onto the stack.
                $context->stack[] = $token;
                $context->stackLength++;
                continue;
            }

            if ($token->is(Token::T_FUNCTION)) {
                // function
                $argc = $token->argc;
                $argv = [];

                $context->stackLength -= $argc - 1;

                for (; $argc > 0; $argc--) {
                    array_unshift($argv, array_pop($context->stack)->value);
                }

                if (!array_key_exists($token->value, static::$functions)) {
                    throw SyntaxError::unexpectedToken($token->value);
                }

                // Push the returned results, if any, back onto the stack.
                $context->stack[] = new Token(
                    Token::T_NUMBER,
                    (string) (static::$functions[$token->value])(...$argv)
                );
                continue;
            }

            if ($token->is(...static::$operators)) {
                // If there are fewer than n values on the stack
                if ($context->stackLength < $token->argc) {
                    throw SyntaxError::missingParameters($token);
                }

                $rightHandSideOp = array_pop($context->stack);
                $leftHandSideOp  = array_pop($context->stack);
                $context->stackLength--;

                $context->stack[] = new Token(
                    Token::T_NUMBER,
                    self::evaluateOperator($token, $leftHandSideOp, $rightHandSideOp)
                );
                continue;
            }

            throw SyntaxError::unexpectedToken($token);
        }

        // That value is the result of the calculation.
        return array_pop($context->stack)->value ?? '0';
    }

    protected static function consumeToken(RuntimeContext $context, Token $token, TokenStream $stream): void
    {
        $consumer = match ($token->type) {
            Token::T_PLUS, Token::T_MINUS, Token::T_TIMES, Token::T_DIV, Token::T_MOD, Token::T_POW => [self::class, 'consumeOperator'],
            Token::T_NUMBER, Token::T_IDENTIFIER => function (RuntimeContext $context, Token $token) {
                $context->queue[] = $token;
            },
            Token::T_OPEN_PARENTHESIS => function (RuntimeContext $context, Token $token) {
                $context->stack[] = $token;
            },
            Token::T_FUNCTION          => [self::class, 'consumeFunction'],
            Token::T_COMMA             => [self::class, 'consumeComma'],
            Token::T_CLOSE_PARENTHESIS => [self::class, 'consumeCloseParenthesis'],
            default                    => throw SyntaxError::unexpectedToken($token)
        };

        $consumer($context, $token, $stream);
    }

    protected static function evaluateOperator(Token $operator, Token $left, Token $right): string
    {
        $left  = $left->value;
        $right = $right->value;

        $previousScale = bcscale();
        bcscale(static::$precision);
        $evaluation = match ($operator->type) {
            Token::T_PLUS  => bcadd($left, $right),
            Token::T_MINUS => bcsub($left, $right),
            Token::T_TIMES => bcmul($left, $right),
            Token::T_DIV   => bcdiv($left, $right),
            Token::T_MOD   => bcmod($left, $right),
            Token::T_POW   => bcpow($left, $right),
        };
        bcscale($previousScale);

        return $evaluation;
    }

    private static function consumeComma(RuntimeContext $context): void
    {
        // If the token is a function argument separator (e.g., a comma):
        $pe = false;

        while ($token = end($context->stack)) {
            if ($token->type === Token::T_OPEN_PARENTHESIS) {
                $pe = true;
                break;
            }

            // Until the token at the top of the stack is a left parenthesis,
            // pop operators off the stack onto the output queue.
            $context->queue[] = array_pop($context->stack);
        }

        // If no left parentheses are encountered, either the separator was misplaced
        // or parentheses were mismatched.
        if ($pe !== true) {
            throw SyntaxError::new('Missing token `(` or misplaced token `,`');
        }
    }

    private static function consumeFunction(RuntimeContext $context, Token $token, TokenStream $stream): void
    {
        // If the token is a function token, then push it onto the stack.
        $context->stack[] = $token;
        $argc             = 0;
        $parenthesis      = 0;

        // we skip the (
        self::consumeToken($context, $stream->next(), $stream);

        if ($stream->peek()) { // more tokens?
            while ($ahead = $stream->next()) {
                self::consumeToken($context, $ahead, $stream);

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
>>>>>>> 5c81a2fa264db036a074f91d670c636f3c690bfa

        return $runtime->run();
    }
}

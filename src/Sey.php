<?php

namespace Felix\BcExpr;

use Felix\BcExpr\Exceptions\SyntaxError;

class Sey
{
    protected static array $functions = [
        'sqrt' => 'bcsqrt',
        'powmod' => 'bcpowmod',
        'pi' => 'bcpi'
    ];

    protected static int $precision = 16;

    protected static array $precedence = [
        Token::T_POW => 4,
        Token::T_TIMES => 3,
        Token::T_DIV => 3,
        Token::T_MOD => 3,
        Token::T_PLUS => 2,
        Token::T_MINUS => 1,
    ];
    protected static array $operators = [
        Token::T_PLUS,
        Token::T_MINUS,
        Token::T_TIMES,
        Token::T_DIV,
        Token::T_MOD,
        Token::T_POW,
    ];

    public static function define(string $function, callable $definition): void
    {
        static::$functions[$function] = $definition;
    }

    public static function precision(?int $precision = null)
    {
        if ($precision === null) {
            return static::$precision;
        }

        static::$precision = $precision;
    }

    public static function parse(string $expression, array $variables = []): string
    {
        $expression = trim($expression);
        if ($expression === '') {
            return '0';
        }

        $tokenStream = TokenStream::create($expression);
        $context = new RuntimeContext();
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
                    (string)(static::$functions[$token->value])(...$argv)
                );
                continue;
            }

            if ($token->is(...static::$operators)) {
                // If there are fewer than n values on the stack
                if ($context->stackLength < $token->argc) {
                    throw SyntaxError::missingParameters($token);
                }

                $rightHandSideOp = array_pop($context->stack);
                $leftHandSideOp = array_pop($context->stack);
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
            Token::T_FUNCTION => [self::class, 'consumeFunction'],
            Token::T_COMMA => [self::class, 'consumeComma'],
            Token::T_CLOSE_PARENTHESIS => [self::class, 'consumeCloseParenthesis'],
            default => throw SyntaxError::unexpectedToken($token)
        };

        $consumer($context, $token, $stream);
    }

    protected static function evaluateOperator(Token $operator, Token $left, Token $right): string
    {
        $left = $left->value;
        $right = $right->value;

        $previousScale = bcscale();
        bcscale(static::$precision);
        $evaluation = match ($operator->type) {
            Token::T_PLUS => bcadd($left, $right),
            Token::T_MINUS => bcsub($left, $right),
            Token::T_TIMES => bcmul($left, $right),
            Token::T_DIV => bcdiv($left, $right),
            Token::T_MOD => bcmod($left, $right),
            Token::T_POW => bcpow($left, $right),
        };
        bcscale($previousScale);
        return $evaluation;
    }

    public static function compare(string $number, string $operator, string $comparison): bool
    {
        $compared = bccomp($number, $comparison, static::$precision);

        return match ($operator) {
            ">" => $compared === 1,
            "<" => $compared === -1,
            ">=" => $compared === 1 || $compared === 0,
            "<=" => $compared === -1 || $compared === 0,
            "=", "==" => $compared === 0
        };
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
        $argc = 0;
        $parenthesis = 0;

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

    private static function consumeOperator(RuntimeContext $context, Token $token): void
    {
        while (!empty($context->stack)) {
            $s = end($context->stack);

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
                    $p1 = static::$precedence[$token->type];
                    $p2 = static::$precedence[$s->type];

                    if (!(($token->is(Token::T_POW) && ($p1 <= $p2)) || ($p1 < $p2))) {
                        break 2;
                    }

                    // Pop o2 off the stack, onto the output queue;
                    $context->queue[] = array_pop($context->stack);
            }
        }

        // push op1 onto the stack.
        $context->stack[] = $token;
    }

    private static function consumeCloseParenthesis(RuntimeContext $context): void
    {
        $pe = false;

        // Until the token at the top of the stack is a left parenthesis,
        // pop operators off the stack onto the output queue
        while ($token = array_pop($context->stack)) {
            if ($token->is(Token::T_OPEN_PARENTHESIS)) {
                // Pop the left parenthesis from the stack, but not onto the output queue.
                $pe = true;
                break;
            }

            $context->queue[] = $token;
        }

        // If the stack runs out without finding a left parenthesis, then there are mismatched parentheses.
        if ($pe === false) {
            throw SyntaxError::unexpectedToken(')');
        }

        // If the token at the top of the stack is a function token, pop it onto the output queue.
        if (($token = end($context->stack)) && $token->is(Token::T_FUNCTION)) {
            $context->queue[] = array_pop($context->stack);
        }
    }
}

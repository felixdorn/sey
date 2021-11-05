<?php

namespace Felix\Sey;

/**
 * @internal
 */
class Scanner
{
    protected static array $operatorsToTokenType = [
        '+' => Token::T_PLUS,
        '-' => Token::T_MINUS,
        '/' => Token::T_DIV,
        '%' => Token::T_MOD,
        '^' => Token::T_POW,
        '*' => Token::T_TIMES,
        '(' => Token::T_OPEN_PARENTHESIS,
        ')' => Token::T_CLOSE_PARENTHESIS,
        ',' => Token::T_COMMA,
    ];
    protected array $tokens;
    protected int $cursor;
    protected int $length;

    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
        $this->length = count($tokens);
        $this->cursor = 0;
    }

    public static function scan(string $code): self
    {
        // This token will be ignored as we directly call $stream->next() in our while loop in the parser.
        $tokens = [new Token(0, '')];

        preg_match_all('/[,+\-*\/^%()]|\d*\.\d+|\d+\.\d*|\d+|[a-z_A-Z!]+[a-z_A-Z0-9]*|[ \t]+/', $code, $matches);

        foreach ($matches[0] as $k => $match) {
            $behind = $matches[0][$k - 1] ?? '';
            $value  = trim($match);

            if ($value === '') {
                continue;
            }

            if (is_numeric($value)) {
                if ($behind === ')') {
                    $tokens[] = new Token(Token::T_TIMES, '*');
                }

                $tokens[] = new Token(Token::T_NUMBER, $value);
                continue;
            }

            $type = static::$operatorsToTokenType[$value] ?? Token::T_IDENTIFIER;

            $lastToken = array_key_last($tokens) !== null ? $tokens[array_key_last($tokens)] : new Token(0, '');

            if ($type === Token::T_OPEN_PARENTHESIS && $lastToken->is(Token::T_IDENTIFIER)) {
                $lastToken->type = Token::T_FUNCTION;
            }

            if ($type === Token::T_OPEN_PARENTHESIS && (is_numeric($behind) || $behind === ')')) {
                $tokens[] = new Token(Token::T_TIMES, '*');
            }

            $tokens[] = new Token($type, $value);
        }

        return new self($tokens);
    }

    public function next(): Token|false
    {
        return next($this->tokens);
    }

    public function prev(): Token|false
    {
        return prev($this->tokens);
    }

    public function peek(): Token|false
    {
        $token = next($this->tokens);
        prev($this->tokens);

        return $token;
    }
}

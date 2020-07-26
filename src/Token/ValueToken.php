<?php
declare(strict_types=1);

namespace Q\Lisp\Token;
use Q\Lisp\Interpreter\Environment;
use Q\Lisp\Misc\Maybe;
use Q\Lisp\Misc\Pair;

class ValueToken extends Token {
    public const POSSIBLE_TOKENS = [
        CharToken::class,
        StringToken::class,
        FloatToken::class,
        IntToken::class,
        BoolToken::class,
        ListToken::class,
        ExprToken::class,
        VarToken::class
    ];

    public function evaluate(Environment $env): Maybe {
        return $this->wrap->evaluate($env);
    }

    public function validate(Environment $env): bool {
        return true;
    }

    public static function parse(Pair $data): Maybe {
        $code = trim($data->first);
        $tokens = $data->second;

        foreach (self::POSSIBLE_TOKENS as $possibleToken) {
            $tokenParse = $possibleToken::parse(new Pair($code, $tokens));
            if ($tokenParse->has_value) {
                return $tokenParse;
            }
        }

        return new Maybe();
    }
}

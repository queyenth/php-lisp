<?php
declare(strict_types=1);

namespace Q\Lisp\Token;

use Q\Lisp\Misc\Pair;
use Q\Lisp\Misc\Maybe;

class ListToken extends Token {
    use EvalAsIs;

    public static function parse(Pair $data): Maybe {
        $code = trim($data->first);
        $tokens = $data->second;

        if ($code[0] === '\'' && $code[1] === '(') {
            $listTokens = [];
            $code = substr($code, 2);
            while ($code[0] !== ')') {
                $value = ValueToken::parse(new Pair($code, $listTokens));
                if ($value->has_value) {
                    $code = trim($value->wrap->first);
                    $listTokens = $value->wrap->second;
                }
                else {
                    fprintf(STDERR, "Exptected ValueToken, got something else in ListToken: %s\n", $data->first);
                    return new Maybe();
                }
            }

            return new Maybe(
                new Pair(
                    substr($code, 1),
                    [...$tokens, new self($listTokens)]
                )
            );
        }

        return new Maybe();
    }
}

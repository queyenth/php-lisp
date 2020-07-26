<?php
declare(strict_types=1);

namespace Q\Lisp\Token;
use Q\Lisp\Misc\Maybe;
use Q\Lisp\Misc\Pair;

class CharToken extends Token {
    use EvalAsIs;

    public static function parse(Pair $data): Maybe {
        $code = trim($data->first);

        if ($code[0] === '\'' && $code[2] === '\'') {
            return new Maybe(
                new Pair(
                    substr($code, 3),
                    [...$data->second, new self($code[1])]
                )
            );
        }

        return new Maybe();
    }
}

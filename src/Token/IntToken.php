<?php
declare(strict_types=1);

namespace Q\Lisp\Token;

use Q\Lisp\Misc\Maybe;
use Q\Lisp\Misc\Pair;

class IntToken extends Token {
    use EvalAsIs;

    public static function parse(Pair $data): Maybe {
        $code = trim($data->first);
        $tokens = $data->second;

        $index = 0;
        if (isset($code[$index]) && $code[$index] === '-') $index++;
        while (isset($code[$index]) && $code[$index] >= '0' && $code[$index] <= '9') $index++;

        if ($index) {
            return new Maybe(
                new Pair(
                    substr($code, $index),
                    [...$tokens, new self(intval(substr($code, 0, $index)))]
                )
            );
        }

        return new Maybe();
    }
}

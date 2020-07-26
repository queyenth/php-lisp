<?php
declare(strict_types=1);

namespace Q\Lisp\Token;
use Q\Lisp\Misc\Maybe;
use Q\Lisp\Misc\Pair;

class StringToken extends Token {
    use EvalAsIs;

    public static function parse(Pair $data): Maybe {
        $code = trim($data->first);

        if ($code[0] === '"') {
            $index = 1;
            while ($index < strlen($code) && $code[$index] !== '"') $index++;
            
            $string = str_replace(
                ["\\n", "\\t", "\\r", "\\v", "\\f"],
                ["\n", "\t", "\r", "\v", "\f"],
                substr($code, 1, $index-1)
            );

            return new Maybe(
                new Pair(
                    substr($code, $index+1),
                    [...$data->second, new self($string)]
                )
            );
        }

        return new Maybe();
    }
}

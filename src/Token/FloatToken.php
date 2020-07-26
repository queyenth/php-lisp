<?php
declare(strict_types=1);

namespace Q\Lisp\Token;

use Q\Lisp\Misc\Maybe;
use Q\Lisp\Misc\Pair;

class FloatToken extends Token {
    use EvalAsIs;

    public static function fromTwoInt(IntToken $full, IntToken $afterDot) {
        $num_length = pow(10, strlen((string)$afterDot->wrap));

        return new self($full->wrap + $afterDot->wrap / $num_length);
    }

    public static function parse(Pair $data): Maybe {
        $parseInt = IntToken::parse(new Pair($data->first, []));
        if ($parseInt->has_value) {
            $code = trim($parseInt->wrap->first);
            $fullPart = $parseInt->wrap->second[0];

            if (strlen($code) && $code[0] === '.') {
                $parseIntAfterDot = IntToken::parse(new Pair(substr($code, 1), []));

                if ($parseIntAfterDot->has_value) {
                    $afterDotPart = $parseInt->wrap->second[0];

                    return new Maybe(new Pair(
                        $parseIntAfterDot->wrap->first,
                        [...$data->second, FloatToken::fromTwoInt($fullPart, $afterDotPart)]
                    ));
                }
                else {
                    return new Maybe(new Pair(
                        $parseIntAfterDot->wrap->first,
                        [...$data->second, FloatToken::fromTwoInt($fullPart, new IntToken(0))]
                    ));
                }
            }
        }

        return new Maybe();
    }
}

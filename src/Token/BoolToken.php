<?php
declare(strict_types=1);

namespace Q\Lisp\Token;
use Q\Lisp\Misc\Maybe;
use Q\Lisp\Misc\Pair;

class BoolToken extends Token {
    use EvalAsIs;

    public const POSSIBLE_VALUES = [
        "true" => true,
        "false" => false,
    ];

    public static function parse(Pair $data): Maybe {
        $code = trim($data->first);
        foreach (self::POSSIBLE_VALUES as $value => $boolValue) {
            if (substr($code, 0, strlen($value)) === $value) {
                return new Maybe(new Pair(
                    substr($code, strlen($value)),
                    [...$data->second, new self($boolValue)]
                ));
            }
        }

        return new Maybe();
    }
}

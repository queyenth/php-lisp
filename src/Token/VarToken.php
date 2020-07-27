<?php
declare(strict_types=1);

namespace Q\Lisp\Token;

use Q\Lisp\Interpreter\Environment;
use Q\Lisp\Misc\Maybe;
use Q\Lisp\Misc\Pair;

class VarToken extends Token {
    public function evaluate(Environment $env): Maybe {
        if (!$this->validate($env)) {
            fprintf(STDERR, "Cannot find '%s' in environment\n", $this->wrap);
            return new Maybe();
        }

        return new Maybe(
            new Pair(
                $env,
                $env->getValue($this->wrap)->wrap
            )
        );
    }

    public function validate(Environment $env): bool {
        return $env->getValue($this->wrap)->has_value;
    }

    public static function parse(Pair $data): Maybe {
        $code = trim($data->first);
        $index = 0;

        $stoppingCharacters = [' ', ')', "\n", "\t", "\r"];

        while ($index < strlen($code) && !in_array($code[$index], $stoppingCharacters)) $index++;

        if ($index) {
            return new Maybe(
                new Pair(
                    substr($code, $index),
                    [...$data->second, new self(substr($code, 0, $index))]
                )
            );
        }

        return new Maybe();
    }
}

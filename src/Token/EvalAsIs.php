<?php
declare(strict_types=1);

namespace Q\Lisp\Token;

use Q\Lisp\Interpreter\Environment;
use Q\Lisp\Misc\Maybe;
use Q\Lisp\Misc\Pair;

trait EvalAsIs {
    public function evaluate(Environment $env): Maybe {
        return new Maybe(
            new Pair(
                $env,
                $this
            )
        );
    }

    public function validate(Environment $env): bool {
        return true;
    }
}

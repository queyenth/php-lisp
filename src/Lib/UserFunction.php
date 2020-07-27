<?php
declare(strict_types=1);

namespace Q\Lisp\Lib;
use Q\Lisp\Interpreter\Environment;
use Q\Lisp\Misc\Maybe;

class UserFunction extends IFunction {
    public function evaluate(Environment $env): Maybe {
        $evaluated = $this->funcBody->evaluate($env);

        if ($evaluated->has_value) {
            return new Maybe($evaluated->wrap->second);
        }
        return new Maybe();
    }
}

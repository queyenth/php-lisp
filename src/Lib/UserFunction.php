<?php
declare(strict_types=1);

namespace Q\Lisp\Lib;
use Q\Lisp\Interpreter\Environment;
use Q\Lisp\Misc\Maybe;

class UserFunction extends IFunction {
    public function evaluate(Environment $env): Maybe {
        return $this->funcBody->evaluate($env);
    }
}

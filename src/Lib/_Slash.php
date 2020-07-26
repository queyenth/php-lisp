<?php
declare(strict_types=1);

namespace Q\Lisp\Lib;

use Q\Lisp\Misc\Maybe;
use Q\Lisp\Misc\Pair;
use Q\Lisp\Interpreter\Environment;
use Q\Lisp\Token\ExprToken;
use Q\Lisp\Token\VarToken;
use Q\Lisp\Token\FloatToken;

class _Slash extends IFunction {
    public function __construct() {
        $this->funcArgs = new ExprToken(
            array(
                new VarToken('arg1'),
                new VarToken('arg2')
            )
        );
    }

    public function evaluate(Environment $env): Maybe {
        return new Maybe(
            new Pair(
                $env,
                new FloatToken($env->getValue('arg1')->wrap->wrap / $env->getValue('arg2')->wrap->wrap)
            )
        );
    }
}

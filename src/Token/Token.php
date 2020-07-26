<?php
declare(strict_types=1);

namespace Q\Lisp\Token;

use Q\Lisp\Interpreter\Environment;
use Q\Lisp\Misc\Maybe;
use Q\Lisp\Misc\Pair;

abstract class Token {
    public $wrap;

    public function __construct($wrap) {
        $this->wrap = $wrap;
    }

    abstract public function evaluate(Environment $env): Maybe;
    abstract public function validate(Environment $env): bool;

    abstract public static function parse(Pair $data): Maybe;
}

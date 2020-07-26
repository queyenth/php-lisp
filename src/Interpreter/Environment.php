<?php
declare(strict_types=1);

namespace Q\Lisp\Interpreter;

use Q\Lisp\Misc\Maybe;
use Q\Lisp\Lib\FunctionFactory;

class Environment {
    const PHP_PREFIX = 'php/';

    public array $values;

    public function __construct(array $values = []) {
        $this->values = $values;
    }

    public function setValue(string $key, $value) {
        $this->values[$key] = $value;
    }

    public function getValue(string $key): Maybe {
        if (isset($this->values[$key])) {
            return new Maybe($this->values[$key]);
        }

        // What if this value is from PHP?
        if (substr($key, 0, strlen(self::PHP_PREFIX)) === self::PHP_PREFIX) {
            return FunctionFactory::createPhpFunction(substr($key, strlen(self::PHP_PREFIX)));
        }

        return new Maybe();
    }
}

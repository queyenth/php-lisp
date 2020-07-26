<?php
declare(strict_types=1);

namespace Q\Lisp\Misc;

class Pair {
    public $first;
    public $second;

    public function __construct($first, $second) {
        $this->first = $first;
        $this->second = $second;
    }
}

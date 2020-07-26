<?php
declare(strict_types=1);

namespace Q\Lisp\Misc;

class Maybe {
    public $wrap = null;
    public bool $has_value = false;

    public function __construct($wrap = null) {
        $this->setWrap($wrap);
    }

    public function setWrap($wrap) {
        if (!is_null($wrap)) {
            $this->wrap = $wrap;
            $this->has_value = true;
        }
    }
}

<?php
declare(strict_types=1);

/*
* <lparen> ::= (
* <rparen> ::= )
* <expr> ::= <lparen><func> [<value>]<rparen>
* <quote> ::= '
* <list> ::= <quote><lparen>[<value>]<rparen>
* <value> ::= <list> | <expr> | <char> | <string> | <int> | <float> | <bool>
* <func> ::= <stringterm>
* <doublequote> ::= "
* <charterm> ::= [a-zA-Z+-=?_-!@#$%^&*()/\]
* <char> ::= <quote><charterm><quote>
* <stringterm> ::= [<charterm>]
* <string> ::= <doublequote><stringterm><doublequote>
* <int> ::= [0-9]
* <float> ::= <int>.<int>
* <bool> ::= true | false
*
* <define> ::= <lparen>define <stringterm> <value><rparen>
* <defun> ::= <lparen>defun <lparen><stringterm> [<stringterm>] <value><rparen>
*/

const _EXPR = 3;
const _LIST = 5;
const _VALUE = 6;
const _FUNC = 7;
const _CHAR = 10;
const _STRING = 12;
const _INT = 13;
const _FLOAT = 14;
const _BOOL = 15;
const _DEFINE = 16;
const _DEFUN = 17;

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

interface Token {}

class _Int implements Token {
    public int $wrap;

    public function __construct(int $wrap) {
        $this->wrap = $wrap;
    }
}

class _Char implements Token {
    public $wrap;

    public function __construct($wrap) {
        $this->wrap = $wrap;
    }
}

class _Float implements Token {
    public float $wrap;

    public function __construct(_Int $full, _Int $afterDot) {
        $num_length = pow(10, strlen((string)$afterDot->wrap));
        $this->wrap = $full->wrap + $afterDot->wrap / $num_length;
    }
}

class _Bool implements Token {
    public const possibleValues = [
        "true" => true,
        "false" => false
    ];

    public bool $wrap;

    public function __construct(bool $wrap) {
        $this->wrap = $wrap;
    }
}

class _String implements Token {
    public string $wrap;

    public function __construct(string $wrap) {
        $this->wrap = $wrap;
    }
}

class _List implements Token {
    public array $wrap;

    public function __construct(array $wrap) {
        $this->wrap = $wrap;
    }
}

class _Func implements Token {
    public string $wrap;

    public function __construct(string $wrap) {
        $this->wrap = $wrap;
    }
}

class _Expr implements Token {
    public array $wrap;

    public function __construct(array $wrap) {
        $this->wrap = $wrap;
    }
}

const parseTable = [
    _CHAR => '_char',
    _STRING => '_string',
    _INT => '_int',
    _FLOAT => '_float',
    _BOOL => '_bool',
    _LIST => '_list',
    _EXPR => '_expr',
];

// <expr> ::= <lparen><func> [<value>]<rparen>
function _expr(Maybe $data): Maybe {
    if ($data->has_value) {
        $code = trim($data->wrap[0]);
        $tokens = $data->wrap[1];

        if ($code[0] == '(') {
            $index = 1;
            while ($code[$index] !== ' ') {
                $index++;
            }

            $func = substr($code, 1, $index-1);
            $exprTokens = [new _Func($func)];
            $code = substr($code, $index);
            while ($code[0] !== ')') {
                $value = _value(new Maybe([
                    $code,
                    $exprTokens
                ]));
                if ($value->has_value) {
                    $code = $value->wrap[0];
                    $exprTokens = $value->wrap[1];
                }
                else {
                    fprintf(STDERR, "Fucked up somewhere, idk!\n");
                    exit(1);
                }
            }

            return new Maybe([
                substr($code, 1),
                [...$tokens, new _Expr($exprTokens)]
            ]);
        }
    }

    return new Maybe();
}

// <list> ::= <quote><lparen>[<value>]<rparen>
function _list(Maybe $data): Maybe {
    if ($data->has_value) {
        $code = trim($data->wrap[0]);
        $tokens = $data->wrap[1];

        if ($code[0] == '\'' && $code[1] == '(') {
            $listTokens = [];
            $code = substr($code, 2);
            while ($code[0] !== ')') {
                $value = _value(new Maybe([
                    $code,
                    $listTokens
                ]));
                if ($value->has_value) {
                    $code = $value->wrap[0];
                    $listTokens = $value->wrap[1];
                }
                else {
                    fprintf(STDERR, "Fucked up somewhere, idk!");
                    exit(1);
                }
            }

            return new Maybe([
                substr($code, 1),
                [...$tokens, new _List($listTokens)]
            ]);
        }
    }

    return new Maybe();
}

// <value> ::= <list> | <expr> | <char> | <string> | <int> | <float> | <bool>
function _value(Maybe $data): Maybe {
    static $possibleTokens = [
        _CHAR,
        _STRING,
        _FLOAT,
        _INT,
        _BOOL,
        _LIST,
        _EXPR
    ];

    if ($data->has_value) {
        $code = $data->wrap[0];
        $tokens = $data->wrap[1];
        
        foreach ($possibleTokens as $possibleToken) {
            $tokenParseFunction = parseTable[$possibleToken];
            $tokenParse = $tokenParseFunction(new Maybe([$code, $tokens]));
            if ($tokenParse->has_value) {
                return $tokenParse;
            }
        }

        return new Maybe();
    }

    return $data;
}

function _int(Maybe $data): Maybe {
    if ($data->has_value) {
        $code = trim($data->wrap[0]);
        $tokens = $data->wrap[1];

        $index = 0;
        while (isset($code[$index]) && $code[$index] >= '0' && $code[$index] <= '9') {
            $index++;
        }

        if ($index == 0) {
            return new Maybe();
        }

        return new Maybe([
            substr($code, $index),
            [...$tokens, new _Int(intval(substr($code, 0, $index)))]
        ]);
    }

    return $data;
}

function _float(Maybe $data): Maybe {
    if ($data->has_value) {
        $parseInt = _int($data);
        if ($parseInt->has_value) {
            $code = trim($parseInt->wrap[0]);

            $fullPart = array_slice($parseInt->wrap[1], -1)[0];

            if (strlen($code) && $code[0] == '.') {
                $parseIntAfterDot = _int(new Maybe([
                    substr($code, 1),
                    []
                ]));

                if ($parseIntAfterDot->has_value) {
                    $afterDotPart = array_slice($parseIntAfterDot->wrap[1], -1)[0];
                    return new Maybe([
                        $parseIntAfterDot->wrap[0],
                        [...$data->wrap[1], new _Float($fullPart, $afterDotPart)]
                    ]);
                }
                else {
                    return new Maybe([
                        $code,
                        [...$data->wrap[1], new _Float($fullPart, new _Int(0))]
                    ]);
                }
            }
        }

        return new Maybe();
    }

    return $data;
}

function _bool(Maybe $data): Maybe {
    if ($data->has_value) {
        $code = trim($data->wrap[0]);
        foreach (_Bool::possibleValues as $value => $boolValue) {
            if (substr($code, 0, strlen($value)) == $value) {
                return new Maybe([
                    substr($code, strlen($value)),
                    [...$data->wrap[1], new _Bool($boolValue)]
                ]);
            }
        }

        return new Maybe();
    }

    return $data;
}

// TODO: Handle escape " functionality
function _string(Maybe $data): Maybe {
    if ($data->has_value) {
        $code = trim($data->wrap[0]);

        if ($code[0] == '"') {
            $index = 1;
            while ($index < strlen($code) && $code[$index] != '"') {
                $index++;
            }

            return new Maybe([
                substr($code, $index+1),
                [...$data->wrap[1], new _String(substr($code, 1, $index-1))]
            ]);
        }

        return new Maybe();
    }

    return $data;
}

function _char(Maybe $data): Maybe {
    if ($data->has_value) {
        $code = trim($data->wrap[0]);

        if ($code[0] == '\'' && $code[2] == '\'') {
            return new Maybe([
                substr($code, 3),
                [...$data->wrap[1], new _Char($code[1])]
            ]);
        }

        return new Maybe();
    }

    return $data;
}

// What does Lexer do?
// Takes code on lisp-like language and transforms it to list of tokens
function lexer(string $code): Maybe {
    // Should we consider just _value as program? Yea!
    return _value(new Maybe([
        $code,
        []
    ]));
}

<?php
declare(strict_types=1);

const VERBOSE = 0;

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

interface Token {
    public function evaluate(array $environment): array;
}

interface CanBeValue {

}

class _Var implements Token, CanBeValue {
    public string $wrap;

    public function __construct(string $wrap) {
        $this->wrap = $wrap;
    }

    public function evaluate(array $environment): array {
        if (!isset($environment[$this->wrap])) {
            echo "Cannot find " . $this->wrap . " in environment\n";
            exit(1);
        }
        return [
            $environment,
            $environment[$this->wrap]
        ];
    }
}

class _Int implements Token, CanBeValue {
    public int $wrap;

    public function __construct(int $wrap) {
        $this->wrap = $wrap;
    }

    public function evaluate(array $environment): array {
        return [
            $environment,
            $this
        ];
    }
}

class _Char implements Token, CanBeValue {
    public $wrap;

    public function __construct($wrap) {
        $this->wrap = $wrap;
    }

    public function evaluate(array $environment): array {
        return [
            $environment,
            $this
        ];
    }
}

class _Float implements Token, CanBeValue {
    public float $wrap;

    public function __construct(float $wrap) {
        $this->wrap = $wrap;
    }

    public static function fromTwoInt(_Int $full, _Int $afterDot) {
        $num_length = pow(10, strlen((string)$afterDot->wrap));

        return new self($full->wrap + $afterDot->wrap / $num_length);
    }

    public function evaluate(array $environment): array {
        return [
            $environment,
            $this
        ];
    }
}

class _Bool implements Token, CanBeValue {
    public const possibleValues = [
        "true" => true,
        "false" => false
    ];

    public bool $wrap;

    public function __construct(bool $wrap) {
        $this->wrap = $wrap;
    }

    public function evaluate(array $environment): array {
        return [
            $environment,
            $this
        ];
    }
}

class _String implements Token, CanBeValue {
    public string $wrap;

    public function __construct(string $wrap) {
        $this->wrap = $wrap;
    }

    public function evaluate(array $environment): array {
        return [
            $environment,
            $this
        ];
    }
}

class _List implements Token, CanBeValue {
    public array $wrap;

    public function __construct(array $wrap) {
        $this->wrap = $wrap;
    }

    public function evaluate(array $environment): array {
        return [
            $environment,
            $this
        ];
    }
}

class _Expr implements Token, CanBeValue {
    public array $wrap;

    const baseFunctions = [
        'define' => '_define',
        'defun' => '_defun',
        '+' => '_plus',
        '-' => '_minus',
        '*' => '_mul',
        '/' => '_div'
    ];

    const phpFunctionsPrefix = 'php/';

    public function __construct(array $wrap) {
        $this->wrap = $wrap;
    }

    public function evaluate(array $environment): array {
        $func = $this->wrap[0];
        $tail = array_slice($this->wrap, 1);
        // Let's search for function in baseFunctions first.
        foreach (self::baseFunctions as $name => $function) {
            if ($func->wrap == $name) {
                if (VERBOSE) {
                    var_dump("Function name: " . $name);
                    var_dump("Args: " . print_r($tail, true));
                    var_dump("Environment: " . print_r($environment, true));
                }
                $result = call_user_func_array([$this, $function], [$environment, $tail]);
                if (VERBOSE) {
                    var_dump("Result: " . print_r($result, true));
                    echo "-----------------------------------\n";
                }
                return $result;
            }
        }

        // Let's search for php functions
        if (substr($func->wrap, 0, strlen(self::phpFunctionsPrefix)) == self::phpFunctionsPrefix) {
            $phpArgs = [];
            foreach ($tail as $arg) {
                if ($arg instanceof _Var) {
                    $arg = $arg->evaluate($environment)[1];
                }

                if ($arg instanceof _List) {
                    $unwrap = $arg->wrap;
                    $arr = [];
                    foreach ($unwrap as $elem) {
                        $arr[] = $elem->evaluate($environment)[1]->wrap;
                    }
                    $phpArgs[] = $arr;
                }
                else {
                    $phpArgs[] = $arg->evaluate($environment)[1]->wrap;
                }
            }
            $funcName = substr($func->wrap, strlen(self::phpFunctionsPrefix));
            $result = call_user_func_array($funcName, $phpArgs);
            if (is_int($result)) {
                $result = new _Int($result);
            }
            elseif (is_bool($result)) {
                $result = new _Bool($result);
            }
            elseif (is_float($result)) {
                $result = new _Float($result);
            }
            elseif (is_array($result)) {
                $result = new _List($result);
            }
            else {
                $result = new _String($result);
            }
            return [
                $environment,
                $result
            ];
        }

        // Let's search for functions in environment
        if (isset($environment[$func->wrap]) && is_array($environment[$func->wrap])) {
            $funcArgs = $environment[$func->wrap][0];
            $validate = fn () => count($funcArgs) == count($tail);
            if ($validate()) {
                $newEnv = [];
                for ($i = 0; $i < count($funcArgs); $i++) {
                    $newEnv[$funcArgs[$i]->wrap] = $tail[$i]->evaluate($environment)[1];
                }
                $funcBody = $environment[$func->wrap][1];
                return [
                    $environment,
                    $funcBody->evaluate($newEnv)[1]
                ];
            }
            else {
                echo "You passed the wrong arguments to the `" . $func->wrap . "`, fool!";
                exit(1);
            }
        }

        echo "What is this `" . $func->wrap . "` nonsense?!";
        exit(1);
    }

    // <define> ::= (define <var_name> <value>)
    protected function _define(array $environment, array $args): array {
        $validate = fn () => $args[0] instanceof _Var && $args[1] instanceof CanBeValue;
        if ($validate()) {
            return [
                array_merge(
                    $environment,
                    [$args[0]->wrap => $args[1]->evaluate($environment)[1]]
                ),
                null
            ];
        }
        else {
            echo "Failed to define\n";
            print_r($args);
            exit(1);
        }
    }

    // <defun> ::= (defun <var> <expr> <expr>)
    protected function _defun(array $environment, array $args): array {
        $validate = fn () => $args[0] instanceof _Var
            && $args[1] instanceof _Expr && $args[2] instanceof _Expr;
        if ($validate()) {
            $funcName = $args[0]->wrap;
            $argsList = $args[1]->wrap;
            $funcBody = $args[2];
            return [
                array_merge(
                    $environment,
                    [$funcName => [$argsList, $funcBody]]
                ),
                null
            ];
        }
        else {
            echo "Failed to defun\n";
            print_r($args);
            exit(1);
        }
    }

    protected function _plus(array $environment, array $args): array {
        $validate = true;
        $argType = "_Int";
        foreach ($args as $arg) {
            if ($arg instanceof _Var) {
                $arg = $arg->evaluate($environment)[1];
            }
            if (!$arg instanceof _Int && !$arg instanceof _Float) {
                $validate = false;
            }
            // If we found float, then, well, it's float return value
            elseif ($arg instanceof _Float) {
                $argType = "_Float";
            }
        }
        if ($validate) {
            return [
                $environment,
                new $argType(array_reduce(
                    $args,
                    fn ($res, $x) => $res + $x->evaluate($environment)[1]->wrap,
                    0
                ))
            ];
        }
        else {
            echo "Failed to plus\n";
            print_r($args);
            exit(1);
        }
    }

    protected function _minus(array $environment, array $args): array {
        $validate = true;
        $argType = "_Int";
        foreach ($args as $arg) {
            if ($arg instanceof _Var) {
                $arg = $arg->evaluate($environment)[1];
            }
            if (!$arg instanceof _Int && !$arg instanceof _Float) {
                $validate = false;
            }
            // If we found float, then, well, it's float return value
            elseif ($arg instanceof _Float) {
                $argType = "_Float";
            }
        }
        if ($validate) {
            return [
                $environment,
                new $argType(array_reduce(
                    $args,
                    fn ($res, $x) => $res - $x->evaluate($environment)[1]->wrap,
                    0
                ))
            ];
        }
        else {
            echo "Failed to minus\n";
            print_r($args);
            exit(1);
        }
    }

    protected function _mul(array $environment, array $args): array {
        $validate = true;
        $argType = "_Int";
        foreach ($args as $arg) {
            if ($arg instanceof _Var) {
                $arg = $arg->evaluate($environment)[1];
            }
            if (!$arg instanceof _Int && !$arg instanceof _Float) {
                $validate = false;
            }
            // If we found float, then, well, it's float return value
            elseif ($arg instanceof _Float) {
                $argType = "_Float";
            }
        }
        if ($validate) {
            return [
                $environment,
                new $argType(array_reduce(
                    $args,
                    fn ($res, $x) => $res * $x->evaluate($environment)[1]->wrap,
                    1
                ))
            ];
        }
        else {
            echo "Failed to mul\n";
            print_r($args);
            exit(1);
        }
    }

    protected function _div(array $environment, array $args): array {
        $validate = true;
        $argType = "_Float";
        foreach ($args as $arg) {
            if ($arg instanceof _Var) {
                $arg = $arg->evaluate($environment)[1];
            }
            if (!$arg instanceof _Int && !$arg instanceof _Float) {
                $validate = false;
            }
        }
        if ($validate) {
            $skip = false;
            return [
                $environment,
                new $argType(array_reduce(
                    $args,
                    function ($res, $x) use ($environment, &$skip) {
                        if ($skip) {
                            $skip = !$skip;
                            return $res;
                        }
                        $res = $res / $x->evaluate($environment)[1]->wrap;
                        return $res;
                    },
                    $args[0]->evaluate($environment)[1]->wrap
                ))
            ];
        }
        else {
            echo "Failed to div\n";
            print_r($args);
            exit(1);
        }
    }
}

// <expr> ::= <lparen><var> [<value>]<rparen>
function _expr(Maybe $data): Maybe {
    if ($data->has_value) {
        $code = trim($data->wrap[0]);
        $tokens = $data->wrap[1];

        if ($code[0] == '(') {
            $var = _var(new Maybe([
                substr($code, 1),
                []
            ]));
            if ($var->has_value) {
                $code = trim($var->wrap[0]);
                $exprTokens = $var->wrap[1];
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
                        fprintf(STDERR, "Error somewhere, idk!\n");
                        exit(1);
                    }
                }

                return new Maybe([
                    substr($code, 1),
                    [...$tokens, new _Expr($exprTokens)]
                ]);
            }
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
                    fprintf(STDERR, "Error somewhere, idk!");
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


// <value> ::= <list> | <expr> | <char> | <string> | <int> | <float> | <bool> | <var>
function _value(Maybe $data): Maybe {
    static $possibleTokens = [
        '_char',
        '_string',
        '_float',
        '_int',
        '_bool',
        '_list',
        '_expr',
        '_var',
    ];

    if ($data->has_value) {
        $code = trim($data->wrap[0]);
        $tokens = $data->wrap[1];
        
        foreach ($possibleTokens as $parseFunction) {
            $tokenParse = $parseFunction(new Maybe([$code, $tokens]));
            if ($tokenParse->has_value) {
                return $tokenParse;
            }
        }

        return new Maybe();
    }

    return $data;
}

function _var(Maybe $data): Maybe {
    if ($data->has_value) {
        $code = trim($data->wrap[0]);
        $index = 0;
        while ($index < strlen($code) && $code[$index] !== ' ' && $code[$index] !== ')') {
            $index++;
        }

        if ($index) {
            return new Maybe([
                substr($code, $index),
                [...$data->wrap[1], new _Var(substr($code, 0, $index))]
            ]);
        }
        else {
            return new Maybe();
        }
    }

    return $data;
}

function _int(Maybe $data): Maybe {
    if ($data->has_value) {
        $code = trim($data->wrap[0]);
        $tokens = $data->wrap[1];

        $index = 0;
        if (isset($code[$index]) && $code[$index] == '-') {
            $index++;
        }
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
                        [...$data->wrap[1], _Float::fromTwoInt($fullPart, $afterDotPart)]
                    ]);
                }
                else {
                    return new Maybe([
                        $code,
                        [...$data->wrap[1], _Float::fromTwoInt($fullPart, new _Int(0))]
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

            $string = str_replace(
                ["\\n", "\\t", "\\r", "\\v", "\\f"],
                ["\n", "\t", "\r", "\v", "\f"],
                substr($code, 1, $index-1)
            );

            return new Maybe([
                substr($code, $index+1),
                [...$data->wrap[1], new _String($string)]
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
    $data = new Maybe([
        $code,
        []
    ]);
    while ($data->has_value && $data->wrap[0] !== "") {
        $data = _value($data);
    }

    return $data;
}

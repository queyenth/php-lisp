<?php
declare(strict_types=1);

namespace Q\Lisp\Token;

use Q\Lisp\Interpreter\Environment;
use Q\Lisp\Misc\Maybe;
use Q\Lisp\Misc\Pair;
use Q\Lisp\Lib\FunctionFactory;

class ExprToken extends Token {
    public function evaluate(Environment $env): Maybe {
        $funcName = $this->wrap[0]->wrap;
        $args = array_slice($this->wrap, 1);

        $f = $env->getValue($funcName);
        if (!$f->has_value) {
            $f = FunctionFactory::get($funcName);
        }

        if ($f->has_value) {
            return $f->wrap->prepareAndEval($env, $args);
        }

        // Exceptions, idk, there should be a way to implmenet it cleaner, but whatever
        switch ($funcName) {
            case 'define':
                if ($args[0] instanceof VarToken) {
                    $value = $args[1]->evaluate($env);
                    if ($value->has_value) {
                        $env->setValue($args[0]->wrap, $value->wrap->second);
                    }
                    else {
                        fprintf(STDERR, "Failed to define %s with value:\n", $args[0]->wrap);
                        fprintf(STDERR, print_r($args[1]->wrap, true) . "\n");
                        return new Maybe();
                    }
                }
                else {
                    fprintf(STDERR, "Expected VarToken, got %s\n", get_class($args[0]));
                    fprintf(STDERR, print_r($this->wrap, true));
                    return new Maybe();
                }
                break;
            case 'defun':
                if ($args[0] instanceof VarToken
                    && $args[1] instanceof ExprToken
                    && $args[2] instanceof ExprToken) {
                    $userFunction = FunctionFactory::create($args[1], $args[2]);
                    if ($userFunction->has_value) {
                        $env->setValue($args[0]->wrap, $userFunction->wrap);
                        break;
                    }
                }
                fprintf(STDERR, "Failed to define function %s, args: %s, body: %s\n", $args[0]->wrap, $args[1]->wrap, $args[2]->wrap);
                return new Maybe();
            default:
                fprintf(STDERR, "%s is not a function name\n", $funcName);
                return new Maybe();
        }

        return new Maybe(
            new Pair(
                $env,
                null
            )
        );
    }

    // TODO: validate
    public function validate(Environment $env): bool {
        return true;
    }

    public static function parse(Pair $data): Maybe {
        $code = trim($data->first);
        $tokens = $data->second;

        if ($code[0] === '(') {
            $var = VarToken::parse(new Pair(substr($code, 1), []));
            if ($var->has_value) {
                $code = trim($var->wrap->first);
                $exprTokens = $var->wrap->second;
                while ($code[0] !== ')') {
                    $value = ValueToken::parse(new Pair($code, $exprTokens));
                    if ($value->has_value) {
                        $code = trim($value->wrap->first);
                        $exprTokens = $value->wrap->second;
                    }
                    else {
                        fprintf(STDERR, "Expected ValueToken, but got something else in Expr: %s\n", trim($data->first));
                        return new Maybe();
                    }
                }

                return new Maybe(
                    new Pair(
                        substr($code, 1),
                        [...$tokens, new self($exprTokens)]
                    )
                );
            }
        }

        return new Maybe();
    }
}

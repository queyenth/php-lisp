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
            case 'if':
                if (isset($args[0])) {
                    $evaluatedCondition = $args[0]->evaluate($env);
                    if ($evaluatedCondition->has_value) {
                        $condition = $evaluatedCondition->wrap->second;
                        $conditionValue = $condition->wrap;
                        if (!is_bool($conditionValue)) {
                            $conditionValue = (bool)$conditionValue;
                        }

                        if ($conditionValue && isset($args[1])) {
                            return $args[1]->evaluate($env);
                        }
                        if (!$conditionValue) {
                            if(isset($args[2])) {
                                return $args[2]->evaluate($env);
                            }
                            break;
                        }
                    }
                    fprintf(STDERR, "Cannot evaluate condition for if\n");
                    return new Maybe();
                }
            case 'let':
                $extendedEnv = clone $env;
                if (isset($args[0]) && ($args[0] instanceof ExprToken)) {
                    // We have to unwrap exprtoken in order to get key -> value stuff
                    $letStatements = $args[0]->wrap;
                    for ($i = 0; $i < count($letStatements); $i+=2) {
                        $evaluateStatement = $letStatements[$i+1]->evaluate($extendedEnv);
                        if ($evaluateStatement->has_value) {
                            $extendedEnv->setValue($letStatements[$i]->wrap, $evaluateStatement->wrap->second);
                        }
                        else {
                            fprintf(STDERR, "Cannot evaluate let statement\n");
                            return new Maybe();
                        }
                    }

                    if (isset($args[1]) && ($args[1] instanceof ExprToken)) {
                        $evaluated = $args[1]->evaluate($extendedEnv);
                        if ($evaluated->has_value) {
                            return new Maybe(
                                new Pair(
                                    $env,
                                    $evaluated->wrap->second
                                )
                            );
                        }
                        else {
                            fprintf(STDERR, "Cannot evaluate let body\n");
                            return new Maybe();
                        }
                    }
                }
                fprintf(STDERR, "In let first argument should be ExprToken\n");
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

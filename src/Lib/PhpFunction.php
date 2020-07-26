<?php
declare(strict_types=1);

namespace Q\Lisp\Lib;
use Q\Lisp\Misc\Maybe;
use Q\Lisp\Misc\Pair;
use Q\Lisp\Interpreter\Environment;
use Q\Lisp\Token\ExprToken;
use Q\Lisp\Token\VarToken;
use Q\Lisp\Token\BoolToken;
use Q\Lisp\Token\StringToken;
use Q\Lisp\Token\IntToken;
use Q\Lisp\Token\FloatToken;
use Q\Lisp\Token\ListToken;

class PhpFunction extends IFunction {
    public string $phpFunctionName;
    public \ReflectionFunction $refFunc;

    public function __construct(string $phpFunctionName) {
        $refFunc = new \ReflectionFunction($phpFunctionName);
        if ($refFunc) {
            $optionalArgs = [];
            $requiredArgs = [];
            foreach ($refFunc->getParameters() as $param) {
                $paramName = $param->getName();
                if ($param->isVariadic()) {
                    $paramName = "..." . $paramName;
                }

                if ($param->isOptional()) {
                    $optionalArgs[] = new VarToken($paramName);
                }
                else {
                    $requiredArgs[] = new VarToken($paramName);
                }
            }

            $args = [
                ...$requiredArgs, new VarToken('&'), ...$optionalArgs
            ];

            $this->funcArgs = new ExprToken($args);
            $this->phpFunctionName = $phpFunctionName;
            $this->refFunc = $refFunc;
        }
    }

    public function evaluate(Environment $env): Maybe {
        $args = [];
        foreach ($this->refFunc->getParameters() as $param) {
            if ($param->isVariadic()) {
                $envParam = $env->getValue("..." . $param->getName());
                if ($envParam->has_value) {
                    foreach ($envParam->wrap as $val) {
                        $args[] = $val->wrap;
                    }
                }
            }
            else {
                $envParam = $env->getValue($param->getName());
                if ($envParam->has_value) {
                    $args[] = $envParam->wrap->wrap;
                }
            }
        }
        $result = call_user_func_array($this->phpFunctionName, $args);
        if (is_int($result)) {
            $result = new IntToken($result);
        }
        elseif (is_bool($result)) {
            $result = new BoolToken($result);
        }
        elseif (is_float($result)) {
            $result = new FloatToken($result);
        }
        elseif (is_array($result)) {
            $result = new ListToken($result);
        }
        else {
            $result = new StringToken($result);
        }

        return new Maybe(
            new Pair(
                $env,
                $result
            )
        );
    }
}

<?php
declare(strict_types=1);

namespace Q\Lisp\Lib;
use Q\Lisp\Misc\Maybe;
use Q\Lisp\Misc\Pair;
use Q\Lisp\Interpreter\Environment;
use Q\Lisp\Token\ExprToken;
use Q\Lisp\Token\VarToken;

abstract class IFunction {
    public ExprToken $funcArgs;
    public ExprToken $funcBody;

    public function __construct(ExprToken $funcArgs, ExprToken $funcBody) {
        $this->funcArgs = $funcArgs;
        $this->funcBody = $funcBody;
    }

    public function prepareAndEval(Environment $env, array $args): Maybe {
        $funcArgsUnwrapped = $this->funcArgs->wrap;
        $optionalParameter = false;

        $processedArgs = clone $env;
        $index = 0;
        $variadicPrefix = '...';
        foreach ($funcArgsUnwrapped as $funcArg) {
            // What does normal funcArg looks like? it's var, only var !
            if (!$funcArg instanceof VarToken) {
                fprintf(STDERR, "Expected %s, but got %s in function %s definition\n", VarToken::class, get_class($funcArg), get_class($this));
                return new Maybe();
            }

            $funcArgValue = $funcArg->wrap;
            // This means we have optional parameter afterwards
            if ($funcArgValue == "&") {
                $optionalParameter = true;
                continue;
            }

            // Check if variable is variadic
            if (substr($funcArgValue, 0, strlen($variadicPrefix)) === $variadicPrefix) {
                // This is variadic, which means it should get whatever count of argument and save it inside, and in PHP functions, for example, we should unwrap it
                $variadicVariable = [];
                for ($i = $index; $i < count($args); $i++) {
                    $evaluatedArg = $args[$i]->evaluate($env);
                    if ($evaluatedArg->has_value) {
                        $variadicVariable[] = $evaluatedArg->wrap->second;
                    }
                    else {
                        fprintf(STDERR, "Failed to evaluate %s when calling function %s\n", get_class($args[$index]), get_class($this));
                        return new Maybe();
                    }
                }
                $processedArgs->setValue($funcArgValue, $variadicVariable);
                break;
            }
            elseif (isset($args[$index])) {
                $evaluatedArg = $args[$index]->evaluate($env);
                if ($evaluatedArg->has_value) {
                    $processedArgs->setValue($funcArgValue, $evaluatedArg->wrap->second);
                }
                else {
                    fprintf(STDERR, "Failed to evaluate %s when calling function %s\n", get_class($args[$index]), get_class($this));
                    return new Maybe();
                }
            }
            elseif (!$optionalParameter) {
                fprintf(STDERR, "Expected %s, but got none in function %s call\n", $funcArgValue, get_class($this));
                return new Maybe();
            }

            $index++;
        }

        $evaluated = $this->evaluate($processedArgs);
        if ($evaluated->has_value) {
            return new Maybe(
                new Pair(
                    $env,
                    $evaluated->wrap
                )
            );
        }

        fprintf(STDERR, "Failed to evaluate %s\n", get_class($this));
        return new Maybe();
    }

    abstract public function evaluate(Environment $env): Maybe;
}

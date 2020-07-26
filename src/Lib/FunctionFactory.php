<?php
declare(strict_types=1);

namespace Q\Lisp\Lib;
use Q\Lisp\Misc\Maybe;
use Q\Lisp\Token\ExprToken;

class FunctionFactory {
    public static function get(string $funcName): Maybe {
        $funcNameExplode = explode('_', $funcName);

        $funcClassName = '_';
        foreach ($funcNameExplode as $part) {
            $funcClassName .= str_replace(
                ['+', '-', '*', '/', '\\', '?', '!', '@', '#', '$', '%', '^', '=', '>', '<'],
                ['Plus', 'Minus', 'Star', 'Slash', 'Backslash', 'Question', 'Exclamation', 'At', 'Pound', 'Dollar',
                 'Percent', 'Accent', 'Equal', 'Greater', 'Less'],
                ucfirst(strtolower($part))
            );
        }

        $fullClassName = str_replace('FunctionFactory', '', self::class) . $funcClassName;

        if (class_exists($fullClassName)) {
            return new Maybe(new $fullClassName());
        }

        return new Maybe();
    }

    public static function create(ExprToken $args, ExprToken $funcBody): Maybe {
        return new Maybe(
            new UserFunction($args, $funcBody)
        );
    }

    public static function createPhpFunction(string $phpFunctionName): Maybe {
        return new Maybe(
            new PhpFunction($phpFunctionName)
        );
    }
}

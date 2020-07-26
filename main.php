<?php
declare(strict_types=1);

require_once('vendor/autoload.php');

use Q\Lisp\Misc\Pair;
use Q\Lisp\Token\ValueToken;
use Q\Lisp\Interpreter\Environment;

function usage() {
    echo "Usage: " . $argv[0] . " -r <code>\n";
    echo "       " . $argv[0] . " -f <file>\n";
    exit(1);
}

// What does Interpretator do?
// Takes list of tokens and, basically, runs it.
function run(array $tokens): void {
    $env = new Environment();
    foreach ($tokens as $token) {
        $res = $token->evaluate($env);
        if ($res->has_value) {
            $env = $res->wrap->first;
        }
        else {
            fprintf(STDERR, "Error happened while evaluating %s\n", get_class($token));
            fprintf(STDERR, "%s", print_r($token->wrap, true));
        }
    }
}

$opts = getopt("f:r:");

if (empty($opts) || count($opts) == 2) {
    echo "Usage: " . $argv[0] . " -r <code>\n";
    echo "       " . $argv[0] . " -f <file>\n";
    exit(1);
}

$code = "";
if (isset($opts['f'])) {
    $filename = $opts['f'];
    if (file_exists($filename) && is_readable($filename)) {
        $code = file_get_contents($filename);
    }
    else {
        echo "File '" . $filename . "' does not exists!\n";
        exit(1);
    }
}
elseif (isset($opts['r'])) {
    $code = $opts['r'];
}

$data = new Pair(
    $code,
    []
);
while ($data->first !== "") {
    $newData = ValueToken::parse($data);
    if ($newData->has_value) {
        $data = $newData->wrap;
    }
    else {
        fprintf(STDERR, "Cannot parse something, stopped at %s\n", $data->first);
        exit(1);
    }
}

run($data->second);

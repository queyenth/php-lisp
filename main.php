<?php
declare(strict_types=1);

require_once('Lexer.php');

function usage() {
    echo "Usage: " . $argv[0] . " -r <code>\n";
    echo "       " . $argv[0] . " -f <file>\n";
    exit(1);
}

// What does Interpretator do?
// Takes list of tokens and, basically, runs it.
function run(Maybe $tokens): void {
    if ($tokens->has_value) {
        if ($tokens->wrap[0] !== "") {
            fprintf(STDERR, "%s left unparsed idk\n", $tokens->wrap[0]);
            exit(1);
        }
        /*var_dump($tokens->wrap[1]);
        die;*/
        // So we should have something like Environment
        $environment = [];
        foreach ($tokens->wrap[1] as $token) {
            $environment = $token->evaluate($environment)[0];
        }

        //var_dump($environment);
    }
}

/*if ($argc !== 2) {
    echo "Usage: " . $argv[0] . " <code>\n";
    exit(1);
}*/

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
run(lexer($code));

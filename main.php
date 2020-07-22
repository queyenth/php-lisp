<?php
declare(strict_types=1);

require_once('Lexer.php');

// What does Interpretator do?
// Takes list of tokens and, basically, runs it.
function run(Maybe $tokens): void {
    if ($tokens->has_value) {
        if ($tokens->wrap[0] !== "") {
            fprintf(STDERR, "%s left unparsed idk\n", $tokens->wrap[0]);
            exit(1);
        }
        foreach ($tokens->wrap[1] as $token) {
            echo $token->evaluate();
        }
    }
}

if ($argc !== 2) {
    echo "Usage: " . $argv[0] . " <code>\n";
    exit(1);
}

$code = $argv[1];
run(lexer($code));

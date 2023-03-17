<?php

$sourceDir = __DIR__ . '/../classes/';
$destinationDir = __DIR__ . '/../minified/';

$files = scandir($sourceDir);

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $sourceFile = $sourceDir . $file;
        $destinationFile = $destinationDir . pathinfo($file, PATHINFO_FILENAME) . '.min.php';

        $sourceCode = file_get_contents($sourceFile);
        $minifiedCode = minifyPhp($sourceCode);
        file_put_contents($destinationFile, $minifiedCode);
    }
}

function minifyPhp(string $code): string
{
    $tokens = token_get_all($code);
    $output = '';

    $prevSpace = false;
    foreach ($tokens as $token) {
        if (is_array($token)) {
            list($id, $text) = $token;

            if ($id == T_COMMENT || $id == T_DOC_COMMENT) {
                continue;
            }

            $output .= $text;
            $prevSpace = false;
        } else {
            if (!$prevSpace && ($token == ' ' || $token == "\t" || $token == "\n" || $token == "\r")) {
                $output .= ' ';
                $prevSpace = true;
            } else {
                $output .= $token;
                $prevSpace = false;
            }
        }
    }

    return $output;
}
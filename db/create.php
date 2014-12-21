<?php

/**
 * Converts raw IDF CSV into (word, idf) pairs suitable for MEAD database build tool.
 */

$csv = isset($argv[1]) ? $argv[1] : null;

$idfs = array();
$handle = fopen(__DIR__.'/'.$csv, "r");

if ($handle) {
    $i = 0;
    while (($line = fgets($handle)) !== false) {
        if ($i > 0) {
            $stuff = explode("\t", $line);
            $info = explode(':', $stuff[0]);
            $pos = $info[1];
            $word = $info[2];
            $idf = $stuff[1];

            if ($pos != 'xxx') {
                $idfs[] = $word.' '.$idf;
            }
        }

        $i++;
    }
} else {
    throw new RuntimeException('cannot read CSV file');
}
fclose($handle);

file_put_contents(__DIR__.'/plidf.txt', implode("", $idfs));

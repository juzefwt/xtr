<?php

$subject = isset($argv[1]) ? $argv[1] : null;

if (is_dir($subject)) {
    if ($handle = opendir($subject)) {
        $files = array();

        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != ".." && strtolower(substr($file, strrpos($file, '.') + 1)) == 'txt') {
                $files[] = $file;
            }
        }

        closedir($handle);

        process($subject, $files);
    }
} elseif (is_file($subject)) {
    $files = array($subject);

    process(realpath(dirname($subject)), $files);
} else {
    throw new RuntimeException('Input not found :(');
}

function process($directory, array $files) {
    $clusterName = strtoupper(basename($directory)).'_CLUSTER';
    $clusterDir = $directory.'/'.$clusterName;

    if (is_dir($clusterDir)) {
        rrmdir($clusterDir);
    }

    mkdir($clusterDir);
    mkdir($clusterDir.'/ccl');
    mkdir($clusterDir.'/docsent');

    $originalSentences = array();
    $processedSentences = array();

    foreach ($files as $file) {
        $fileName = getFileName($file);
        $xml = $fileName.'.xml';

        $wcrftCommand = 'wcrft-app nkjp.ini -i text %s/%s -O %s/ccl/%s';
        shell_exec(sprintf($wcrftCommand, $directory, $file, $clusterDir, $xml));

        $chunkList = new SimpleXMLElement(file_get_contents($clusterDir.'/ccl/'.$xml));
        foreach ($chunkList as $paragraph) {
            foreach ($paragraph as $sentence) {
                foreach ($sentence as $tokUp) {
                    $originalSentence = array();
                    $processedSentence = array();

                    foreach ($tokUp as $tok) {
                        $orth = (string)$tok->orth;
                        $base = (string)$tok->lex->base;

                        if ((string)$tok->lex->ctag == 'interp') {
                            continue;
                        }

                        if (trim($base) == '') {
                            continue;
                        }

                        $originalSentence[] = $orth;
                        $processedSentence[] = $base;
                    }

                    $originalSentences[strtoupper($fileName)][] = implode(' ', $originalSentence);
                    $processedSentences[strtoupper($fileName)][] = implode(' ', $processedSentence);
                }
            }
        }

        writeDocsent($processedSentences, $clusterDir.'/docsent', $fileName);
        writeDocsent($originalSentences, $clusterDir.'/docsent', $fileName, true);
    }

    $clusterConfig = new SimpleXMLElement('<CLUSTER LANG="POL"></CLUSTER>');

    foreach (array_keys($processedSentences) as $fileName) {
        $doc = $clusterConfig->addChild('D');
        $doc->addAttribute('DID', strtoupper($fileName));
    }

    $dom = new DOMDocument('1.0', 'utf-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($clusterConfig->asXML());

    file_put_contents($clusterDir.'/'.$clusterName.'.cluster', $dom->saveXML());

    $extractPath = $clusterDir.'/'.$clusterName.'.extract';

    shell_exec('perl -Mutf8 -CS /usr/local/share/mead/bin/mead.pl -extract -output '.$extractPath.' '.$clusterDir);

    $extract = new SimpleXMLElement(file_get_contents($extractPath));
    foreach ($extract as $sentence) {
        $doc = $sentence['DID'];
        $sentenceIndex = $sentence['SNO'] - 1;
        echo $originalSentences[strtoupper($doc)][$sentenceIndex].PHP_EOL;
    }
}

function writeDocsent($sentences, $path, $documentName, $raw = false) {
    $docsentTemplate = '<?xml version="1.0" encoding="utf-8"?><!DOCTYPE DOCSENT SYSTEM "/clair/tools/MEAD3/dtd/docsent.dtd"><DOCSENT></DOCSENT>';
    $docsent = new SimpleXMLElement($docsentTemplate);
    $docsent->addAttribute('DID', strtoupper($documentName));
    $docsent->addAttribute('LANG', 'POL');
    $body = $docsent->addChild('BODY');
    $text = $body->addChild('TEXT');

    foreach ($sentences[$documentName] as $i => $ps) {
        $snt = $text->addChild('S', $ps);
        $snt->addAttribute('PAR', 1);
        $snt->addAttribute('RSNT', $i+1);
        $snt->addAttribute('SNO', $i+1);
    }

    $dom = new DOMDocument('1.0', 'utf-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($docsent->asXML());

    $fileName = $raw ? strtoupper($documentName.'_raw') : strtoupper($documentName);
    file_put_contents($path.'/'.$fileName.'.docsent', $dom->saveXML());
}

function getFileName($file) {
    $data = explode('.', $file);

    return $data[0];
}

function rrmdir($dir) { 
   if (is_dir($dir)) { 
     $objects = scandir($dir); 
     foreach ($objects as $object) { 
       if ($object != "." && $object != "..") { 
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object); 
       } 
     } 
     reset($objects); 
     rmdir($dir); 
   } 
 } 

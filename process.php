<?php

print_R($argv);die;

shell_exec('wcrft-app nkjp.ini -i text test.txt -O test.xml');

$chunkList = new SimpleXMLElement(file_get_contents('test.xml'));
$original = file_get_contents('test.txt');

$originalSentences = array();
$processedSentences = array();

foreach ($chunkList as $chunkLevelOne) {
    foreach ($chunkLevelOne as $sentence) {
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

            $originalSentences[] = implode(' ', $originalSentence);
            $processedSentences[] = implode(' ', $processedSentence);
        }
    }
}

print_r($originalSentences);
// die;

$docsent = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><!DOCTYPE DOCSENT SYSTEM "/clair/tools/MEAD3/dtd/docsent.dtd"><DOCSENT DID="D1" LANG="POL"></DOCSENT>');

$body = $docsent->addChild('BODY');
$text = $body->addChild('TEXT');

foreach ($processedSentences as $i => $ps) {
    $snt = $text->addChild('S', $ps);
    $snt->addAttribute('PAR', 1);
    $snt->addAttribute('RSNT', $i+1);
    $snt->addAttribute('SNO', $i+1);
}

$dom = new DOMDocument('1.0', 'utf-8');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($docsent->asXML());

file_put_contents('./TEST/docsent/D1.docsent', $dom->saveXML());

$config = "<?xml version='1.0'?>
<CLUSTER LANG='POL'>
        <D DID='D1' />
</CLUSTER>";

file_put_contents('./TEST/TEST.cluster', $config);

$extractPath = '/home/juzef/PWR/MGR/prog/TEST.extract';
shell_exec('perl -Mutf8 -CS /home/juzef/PWR/MGR/mead/bin/mead.pl -extract -output '.$extractPath.' /home/juzef/PWR/MGR/prog/TEST');

$extract = new SimpleXMLElement(file_get_contents($extractPath));
foreach ($extract as $sentence) {
    foreach ($sentence->attributes() as $k => $v) {
        if ($k == 'SNO') {
            $sentenceIndex = $v - 1;
            echo $originalSentences[$sentenceIndex].PHP_EOL;
        }
    }
}



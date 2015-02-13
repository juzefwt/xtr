<?php

use Symfony\Component\HttpFoundation\Request;

require_once __DIR__.'/../vendor/autoload.php'; 

$app = new Silex\Application(); 
$app['debug'] = true;

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));
$twig = $app['twig'];
$twig->addExtension(new \Entea\Twig\Extension\AssetExtension($app));
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

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

$app->match('/', function(Request $request) use ($app) {

    if ($request->isMethod('POST')) {
        $text = $request->request->get('text');

        $extractMode = $request->request->get('extract_mode');
        $extractLength = $request->request->get('extract_length');
        $extractDenomination = $request->request->get('extract_denomination');
        $extractBase = $request->request->get('extract_base');

        $oldmask = umask(0);

        $workingDirPath = '/tmp/xtr';
        shell_exec(sprintf('chmod -R 0777 %s', $workingDirPath));
        chown($workingDirPath, 465);

        if (file_exists($workingDirPath)) {
            rrmdir($workingDirPath.'/TEXT');
        }

        $clusterDirPath = $workingDirPath.'/TEXT';

        if (!mkdir($clusterDirPath, 0777)) {
            throw new RuntimeException('Cannot create cluster directory');
        }

        umask($oldmask);

        $scriptPath = __DIR__.'/../../process.php';

        file_put_contents($clusterDirPath.'/TEXT1.txt', $text);

        $cmd = sprintf(
            'php %s %s -mode %s -%s -%s %s',
            $scriptPath,
            $clusterDirPath,
            $extractMode,
            ($extractBase == 'sentences' ? 's' : 'w'),
            ($extractDenomination == 'percent' ? 'p' : 'a'),
            $extractLength
        );

        shell_exec($cmd);

        $rawDocsentPath = $clusterDirPath.'/TEXT_CLUSTER/docsent/TEXT1_RAW.docsent';
        $rawDocsentXml = new SimpleXMLElement(file_get_contents($rawDocsentPath));

        $extractPath = $clusterDirPath.'/TEXT_CLUSTER/TEXT_CLUSTER.extract';
        $extractXml = new SimpleXMLElement(file_get_contents($extractPath));
        $extract = array();

        foreach ($extractXml as $sentence) {
            //$doc = $sentence['DID'];

            foreach ($rawDocsentXml->BODY->TEXT->children() as $s) {
                if ((int)$s['SNO'] == (int)$sentence['SNO']) {
                    $extract[] = (string) $s;
                }
            }
        }

        $modes = array(
            'default' => 'domyślny',
            'ner' => 'nazwy własne',
            'lr' => 'LexRank',
        );

        $extractSpec = array();
        $extractSpec[] = $extractLength.($extractDenomination == 'percent' ? '%' : '').' '.($extractBase == 'sentences' ? 'zdań' : 'słów');
        $extractSpec[] = 'tryb: '.$modes[$extractMode];

        return $app['twig']->render('index.html.twig', array(
            'text' => $text,
            'extract' => $extract,
            'extract_spec' => implode(', ', $extractSpec),
        ));
    }

    return $app['twig']->render('index.html.twig');
})
->bind('homepage'); 

$app->run(); 

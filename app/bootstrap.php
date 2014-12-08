<?php

use Symfony\Component\HttpFoundation\Request;
use Knp\Provider\ConsoleServiceProvider;
use Winkiel\DatePeriod;
use Winkiel\EnergyCalculator;
use Winkiel\Statistics;
use Yosymfony\Silex\ConfigServiceProvider\ConfigServiceProvider;
use Winkiel\Provider\SolarServiceProvider;
use Winkiel\Provider\TemperatureServiceProvider;


require_once __DIR__.'/vendor/autoload.php'; 

$app = new Silex\Application(); 
$app['debug'] = true;

$app->register(new ConfigServiceProvider(array(
    __DIR__.'/config',
)));
$config = $app['configuration']->load('parameters.yml');

$app->register(new SolarServiceProvider());
$app->register(new TemperatureServiceProvider());
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => $config['database'],
));

$app->register(new ConsoleServiceProvider(), array(
    'console.name'              => 'winkiel',
    'console.version'           => '1.0.0',
    'console.project_directory' => __DIR__.'/..'
));

return $app;

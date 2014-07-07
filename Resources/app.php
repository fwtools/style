<?php

set_include_path(__DIR__."/../:.");
error_reporting(-1);

require('autoload.php');
require('functions.php');

use Arya\Request;
use Arya\Response;

try {
	$db = connectDatabase();
} catch(Exception $e) {
	exit;
}

$injector = new Auryn\Provider(new Auryn\ReflectionPool);
$injector->share($db);

$app = (new \Arya\Application($injector))
    ->setOption("routing.cache_file", __DIR__."/../route.cache")

	->route('GET', '/', function() {
		return (new Response)->setHeader('Location', 'http://fwtools.de/style')->setStatus(301);
	})

	->route('GET', '/bettersunfire/style.css', 'Style/BetterSunfire::main')
	->route('GET', '/epicsunfire/style.css', 'Style/EpicSunfire::main')
	->route('GET', '/flatlight/style.css', 'Style/FlatLight::main')
	->route('GET', '/lightnoise/style.css', 'Style/LightNoise::main')

	->run();

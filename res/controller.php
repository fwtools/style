<?php

error_reporting(E_ALL);
set_include_path(__DIR__ . "/../:/usr/share/php:.");

require 'functions.php';
require 'config.php';

$loader = require 'vendor/autoload.php';
$loader->add('App\\', __DIR__ . '/../src/App');
$loader->add('BetterSunfire\\', __DIR__ . '/../src/BetterSunfire');
$loader->add('EpicSunfire\\', __DIR__ . '/../src/EpicSunfire');
$loader->add('LightNoise\\', __DIR__ . '/../src/LightNoise');
$loader->add('FlatLight\\', __DIR__ . '/../src/FlatLight');
$loader->add('KStyle\\', __DIR__ . '/../src/KStyle');

use Arya\Request,
    Arya\Response;

try {
	$db = require 'database.php';
} catch(Exception $e) {
	header("HTTP/1.1 500 Internal Server Error");
	header("Status: 500 Internal Server Error");
	exit;
}

$injector = new Auryn\Provider(new Auryn\ReflectionPool);
$injector->share($db);

(new \Arya\Application($injector))
    ->setOption('routing.cache_file', __DIR__ . '/../route.cache')

	->route('GET', '/', function() {
		return (new Response)->setHeader('Location', 'http://fwtools.de/style')->setStatus(301);
	})

	->before(function (Request $request) use (&$injector) {
        $components = new \App\Components(
            array_intersect(
                glob(__DIR__."/App/Components/*"),
                array_map('strtolower', array_keys($request->getAllQueryParameters()))
            ), $injector
        );

		$injector->share($components);
		$injector->share(new \App\StyleCache($request['REQUEST_URI_PATH'], $components));
	}, ["priority" => 1])

	->before(function (Response $response, \App\StyleCache $cache) {
		if (($style = $cache->get()) !== false) {
			$response->setBody($style);
			return true;
		}
	})

	->route('GET', '/bettersunfire/v1/style.css', 'BetterSunfire/App::main')
	->route('GET', '/epicsunfire/v1/style.css', 'EpicSunfire/App::main')
    ->route('GET', '/lightnoise/v1/style.css', 'LightNoise/App::main')
	->route('GET', '/flatlight/v1/style.css', 'FlatLight/App::main')
	->route('GET', '/kstyle/v1/style.css', 'KStyle/App::main')

	->after(function (Response $response, \App\StyleCache $cache) {
        $cache->set($response->getBody());
	})

	->run();

<?php

error_reporting(-1);

require __DIR__ . '/../autoload.php';
require __DIR__ . '/../functions.php';

use Arya\Request,
    Arya\Response;
use App\StyleCache;

try {
	$db = connectDatabase();
} catch(Exception $e) {
	exit;
}

$injector = new Auryn\Provider(new Auryn\ReflectionPool);
$injector->share($db);

(new \Arya\Application($injector))
    ->setOption("routing.cache_file", __DIR__."/../route.cache")

	->route('GET', '/', function() {
		return (new Response)->setHeader('Location', 'http://fwtools.de/style')->setStatus(302);
	})

	->before(function (Request $request) use (&$injector) {
		$injector->share($components = new \App\Components(array_intersect(glob(__DIR__."/App/Components/*"), array_map('strtolower', array_keys($request->getAllQueryParameters()))), $injector));
		$injector->share(new StyleCache($request['REQUEST_URI_PATH'], $components));
	}, ["priority" => 100])

	->before(function (Response $response, StyleCache $cache) {
		if (($style = $cache->get()) !== false) {
			$response->setBody($style);

			return true;
		}
	})

	->route('GET', '/bettersunfire/style.css', 'Style/bettersunfire/App::main')
	->route('GET', '/epicsunfire/style.css', 'Style/epicsunfire/App::main')
	->route('GET', '/flatlight/style.css', 'Style/flatlight/App::main')
	->route('GET', '/lightnoise/style.css', 'Style/lightnoise/App::main')
	->route('GET', '/kstyle/style.css', 'Style/kstyle/App::main')

	->after(function (Response $response, StyleCache $cache) {
		$cache->set($response->getBody());
	})

	->run();

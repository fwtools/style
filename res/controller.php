<?php

error_reporting(E_ALL);
set_include_path(__DIR__ . "/../:/usr/share/php:.");

require 'functions.php';
require 'config.php';

$loader = require 'vendor/autoload.php';

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

	->before(function (Request $request, Response $response) use (&$injector) {
        $response->setHeader('Content-Type', 'text/css; charset=utf-8');

        $components = new \App\Components(
            array_intersect(
                glob(__DIR__."/App/Components/*"),
                array_map('strtolower', array_keys($request->getAllQueryParameters()))
            ), $injector
        );

		$injector->share($components);
		$injector->share($cache = new App\StyleCache($request['REQUEST_URI_PATH'], $components));

        /* CACHE */
        $time = 240;
        $exp_gmt = gmdate("D, d M Y H:i:s", time() + $time * 60) . " GMT";
        $mod_gmt = gmdate("D, d M Y H:i:s", $cache->getTime()) . " GMT";

        $response->setHeader('Expires', $exp_gmt);
        $response->setHeader('Last-Modified', $mod_gmt);
        $response->setHeader('Cache-Control', 'private, max-age=' . (60 * $time));
        $response->addHeader('Cache-Control', 'post-check=' . (60 * $time - 10));
        /* // CACHE */
	}, ["priority" => 1])

	->before(function (Response $response, App\StyleCache $cache) {
		if (($style = $cache->get()) !== false) {
			$response->setBody($style);
			return true;
		}
	})

	->route('GET', '/bettersunfire/v1/style.css', 'BetterSunfire\BetterSunfire::main')
	->route('GET', '/epicsunfire/v1/style.css', 'EpicSunfire\EpicSunfire::main')
    ->route('GET', '/lightnoise/v2/style.css', 'LightNoise\LightNoise::main')
	->route('GET', '/flatlight/v1/style.css', 'FlatLight\FlatLight::main')
	->route('GET', '/kstyle/v1/style.css', 'KStyle\KStyle::main')

	->after(function (Request $request, Response $response, App\StyleCache $cache) {
        if(!endsWith($request->get('REQUEST_URI_PATH'), '.css')) {
            return;
        }

        var_dump($response);

        require_once 'lib/CssMin.php';

        $filters = [
            "ImportImports"                 => false,
            "RemoveComments"                => true,
            "RemoveEmptyRulesets"           => true,
            "RemoveEmptyAtBlocks"           => true,
            "ConvertLevel3AtKeyframes"      => false,
            "ConvertLevel3Properties"       => true,
            "Variables"                     => true,
            "RemoveLastDelarationSemiColon" => true
        ];

        $plugins = [
            "Variables"                     => true,
            "ConvertFontWeight"             => true,
            "ConvertHslColors"              => true,
            "ConvertRgbColors"              => true,
            "ConvertNamedColors"            => true,
            "CompressColorValues"           => true,
            "CompressUnitValues"            => true,
            "CompressExpressionValues"      => true
        ];

        $body = $response->getBody();
        var_dump($body);
        print "<br><br><br>";

        $body = CssMin::minify($body, $filters, $plugins);
        var_dump($body);

        $response->setBody($body);
        $cache->set($body);
	})

	->run();

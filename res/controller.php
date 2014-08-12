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
        if(!endsWith($request['REQUEST_URI_PATH'], '.css')) {
            return;
        }

        $response->setHeader('Content-Type', 'text/css; charset=utf-8');

        $components = new \App\Components(
            array_intersect(
				array_map(function ($item) { return substr(array_reverse(explode("/", $item))[0], 0, strrpos($item, ".") - strlen($item)); }, glob(__DIR__."/../src/App/Component/*")),
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

        $response->setBody($response->getBody() . implode(iterator_to_array($components->getAllStyles())));
        /* // CACHE */
	}, ["priority" => 1])

	->before(function (Request $request, Response $response, App\StyleCache $cache) {
        if($request->hasQueryParameter('mat')) {
            $track_id = md5($request->getStringQueryParameter('mat'));
            $response->setBody("@import 'track/track.php?{$track_id}';" . $response->getBody());
        }

        try {
            $world = $request->getStringQueryParameter('world');
            $worlds = [
                'de1', 'de2', 'de3', 'de4', 'de5', 'de6', 'de7', 'de8', 'de9',
                'de10', 'de11', 'de12', 'de13', 'de14', 'af', 'rp'
            ];

            if(!in_array($world, $worlds)) {
                throw new Exception('unknown world');
            }

            $response->setBody("@import '/event/style.css?world={$world}';" . $response->getBody());
        } catch(\Exception $e) { }

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

    ->route('GET', '/flatlight/v1/i/{name:[A-Za-z0-9_-]+}.{extension}', 'FlatLight\FlatLight::image')

	->after(function (Request $request, Response $response, App\StyleCache $cache) {
        if(!$response->hasHeader('Content-Type'))
            return;

        if(!startsWith($response->getHeader('Content-Type'), 'text/css'))
            return;

        if($cache->get() !== false)
            return;

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
        $body = CssMin::minify($body, $filters, $plugins);

        $response->setBody($body);

        if($response->getStatus() === 200 && !empty($body)) {
            $cache->set($body);
        }
	})

	->run();

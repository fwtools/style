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
} catch (Exception $e) {
	header("HTTP/1.1 500 Internal Server Error");
	header("Status: 500 Internal Server Error");
    header("Content-Type: text/plain");
	die('database connection error, please check your config.');
}

$injector = new Auryn\Provider(new Auryn\ReflectionPool);
$injector->share($db);

$cacheUsed = false;

(new \Arya\Application($injector))
    ->setOption('routing.cache_file', __DIR__ . '/../route.cache')

	->route('GET', '/', function() {
		return (new Response)->setHeader('Location', 'http://fwtools.de/style')->setStatus(301);
	})

	->before(function (Request $request, Response $response) use ($injector, &$cacheUsed) {
        if (!endsWith($request['REQUEST_URI_PATH'], '.css'))
            return false;

        $response->setHeader('Content-Type', 'text/css; charset=utf-8');

        if (!endsWith($request['REQUEST_URI_PATH'], 'style.css'))
            return false;

        $components = array_map('strtolower', array_keys($request->getAllQueryParameters()));

        $components = new \App\Components($components, $injector);
        $cache = new App\StyleCache($request['REQUEST_URI_PATH'], $components);

        $injector->share($components);
        $injector->share($cache);

        if (($style = $cache->get()) !== false) {
            $response->setBody($style);
            $cacheUsed = true;
            return true;
        } else {
            return false;
        }
	})

	->route('GET', '/bettersunfire/v1/style.css', 'BetterSunfire\BetterSunfire::main')
    ->route('GET', '/bettersunfire/v1/event.css', 'BetterSunfire\BetterSunfire::event')
	->route('GET', '/bettersunfire/v1/i/{name:[A-Za-z0-9_-]+}.{extension}', 'BetterSunfire\BetterSunfire::image')

    ->route('GET', '/epicsunfire/v1/style.css', 'EpicSunfire\EpicSunfire::main')

    ->route('GET', '/lightnoise/v2/style.css', 'LightNoise\LightNoise::main')
    ->route('GET', '/lightnoise/v2/event.css', 'LightNoise\LightNoise::event')
    ->route('GET', '/lightnoise/v2/i/{name:[A-Za-z0-9_-]+}.{extension}', 'LightNoise\LightNoise::image')

	->route('GET', '/flatlight/v1/style.css', 'FlatLight\FlatLight::main')
    ->route('GET', '/flatlight/v1/event.css', 'FlatLight\FlatLight::event')
    ->route('GET', '/flatlight/v1/i/{name:[A-Za-z0-9_-]+}.{extension}', 'FlatLight\FlatLight::image')

	->route('GET', '/kstyle/v2/style.css', 'kstyle\kstyle::main')

    ->route('GET', '/track/record/{id}/{x}/{y}', 'App\Track::addRecord')
    ->route('GET', '/track.css', 'App\Track::css')
    ->route('GET', '/track/map/{id:.+}', 'App\Track::singleUserMap')
    ->route('GET', '/track/map', 'App\Track::multiUserMap')
    ->route('GET', '/npcs.css', 'App\NPCs::css')
    ->route('GET', '/event/record', 'App\Event::addRecord')

	->after(function (Request $request, Response $response) use ($injector, &$cacheUsed) {
		if (!$response->hasHeader('Content-Type') || !startsWith($response->getHeader('Content-Type'), 'text/css'))
			return;

        if (!endsWith($request['REQUEST_URI_PATH'], 'style.css'))
            return;

        $injector->execute(function(Response $response, App\StyleCache $cache) {
            $time = 240;
            $exp_gmt = gmdate("D, d M Y H:i:s", time() + $time * 60) . " GMT";
            $mod_gmt = gmdate("D, d M Y H:i:s", $cache->getTime()) . " GMT";

            $response->setHeader('Expires', $exp_gmt);
            $response->setHeader('Last-Modified', $mod_gmt);
            $response->setHeader('Cache-Control', 'private, max-age=' . (60 * $time));
            $response->addHeader('Cache-Control', 'post-check=' . (60 * $time - 10));
        });

		if ($cacheUsed) {
			return;
		}

		$injector->execute(function (Request $request, Response $response, App\StyleCache $cache, App\Components $components) {
            $body = $response->getBody();

            foreach($components->getAllStyles() as $style) {
                $body.= $style;
            }

            $imports = [];
            $body = preg_replace_callback('#(@import\s[^;]+;)#', function ($m) use (&$imports) {
                $imports[] = $m[1];
                return "";
            }, $body);
            $body = implode($imports) . $body;

            $autoprefixer = new Autoprefixer;

            try {
                $css = $autoprefixer->compile($body);
            } catch (AutoprefixerException $error) {
                echo $error->getMessage();
            } catch (Exception $error) {
                echo $error->getMessage();
            }

			if ($request->hasQueryParameter('import')) {
				$body .= '@import url("'.$request->getStringQueryParameter('import').'")';
			}

            $body = preg_replace('#\s+#', ' ', $body);
            $body = preg_replace('#/\*.*?\*/#s', '', $body);
            $body = str_replace('; ', ';', $body);
            $body = str_replace(': ', ':', $body);
            $body = str_replace(' {', '{', $body);
            $body = str_replace('{ ', '{', $body);
            $body = str_replace(', ', ',', $body);
            $body = str_replace('} ', '}', $body);
            $body = str_replace(';}', '}', $body);
            $body = trim($body);

			$response->setBody($body);

			if($response->getStatus() === 200 && !empty($body)) {
				$cache->set($body);
			}
		});
	})

    ->after(function (Request $request, Response $response) use ($injector) {
        if (!$response->hasHeader('Content-Type') || !startsWith($response->getHeader('Content-Type'), 'text/css'))
            return;

        if (!endsWith($request['REQUEST_URI_PATH'], 'style.css'))
            return;

        $body = $response->getBody();

        try {
            $world = $request->getStringQueryParameter('world');

            $worlds = [
                'de1', 'de2', 'de3', 'de4', 'de5', 'de6', 'de7', 'de8', 'de9',
                'de10', 'de11', 'de12', 'de13', 'de14', 'af', 'rp'
            ];

            if(!in_array($world, $worlds)) {
                throw new Exception('unknown world');
            }

            $body = "@import 'event.css?world={$world}';" . $body;
        } catch (\Exception $e) { $body = "/* unknown world */" . $body; $world = ""; }

        $body.= "#x54y113 a[href='main.php?arrive_eval=drinkwater']:after{width:0;height:0;display:inline-block;content:url('/event/record?event=pensal-available&world={$world}')}";

        if ($request->hasQueryParameter('mat')) {
            $track_id = md5($request->getStringQueryParameter('mat'));
            $body = "@import '/track.css?{$track_id}';" . $body;
        }


		$response->setBody($body);
    }, ['priority' => 100])

	->run();

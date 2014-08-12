<?php

date_default_timezone_set('UTC');

function inRange($min, $curr, $max) {
	return min($max, max($curr, $min));
}

function startsWith($haystack, $needle) {
    return $needle === "" || strpos($haystack, $needle) === 0;
}

function endsWith($haystack, $needle) {
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}

function getWorldFromUrl($url) {
	if(!preg_match("~http://(www\.)?(welt(\d+)|rpsrv|afsrv)\.(freewar|intercyloon)\.de/.*~", $url, $match)) {
		return "";
	}

	if(!empty($match[3]))
		return "de{$match[3]}";

	return substr($match[2], 0, 2);
}

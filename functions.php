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

<?php

date_default_timezone_set('UTC');

require 'config.php';

function connectDatabase() {
	$db = new \BenchPDO("mysql:host=".DB_HOST.";dbname=".DB_DB.";charset=utf8;charset=utf8", DB_USER, DB_PASS);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	return $db;
}

function inRange($min, $curr, $max) {
	return min($max, max($curr, $min));
}

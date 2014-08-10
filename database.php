<?php

$__connect = function($host, $user, $pass, $db) {
	$db = new PDO("mysql:host={$host};dbname={$db};charset=utf8;charset=utf8", $user, $pass);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	return $db;
};

return $__connect(DB_HOST, DB_USER, DB_PASS, DB_DB);

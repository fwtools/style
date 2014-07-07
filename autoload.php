<?php

spl_autoload_register(function($class) {
	if (!preg_match("@^(App)\\\\@", $class))
		return;

	$class = str_replace('\\', '/', $class);
	$file = __DIR__ . "/{$class}.php";

    if (file_exists($file)) {
		require $file;
	}
});

require __DIR__ . "/Arya/src/bootstrap.php";

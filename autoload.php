<?php

spl_autoload_register(function($class) {
	if (!preg_match("@^(App|Style)\\\\@", $class))
		return;

	$class = str_replace('\\', '/', $class);
	if (substr($class, 0, 6) == "Style/") {
		$file = STYLES_PATH . "/".substr($class, 6).".php";
	} else {
		$file = __DIR__ . "/{$class}.php";
	}

    if (file_exists($file)) {
		require $file;
	}
});

require __DIR__ . "/Arya/src/bootstrap.php";

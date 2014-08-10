<?php

namespace App;

class StyleCache {
	private $components;
	private $file;

	public function __construct ($path, Components $components) {
		$this->components = $components->getAll();
		sort($components);

		$this->file = CACHE_DIR . str_replace($path, "/", "-") . "-" . implode("-", $components) . ".css";

		var_dump($path);
	}

	public function get() {
		return @file_get_contents($this->file);
	}

	public function set($style) {
		file_put_contents($this->file, $style);
	}
}

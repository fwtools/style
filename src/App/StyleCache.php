<?php

namespace App;

class StyleCache {
	private $components;
	private $file;

	public function __construct ($path, Components $components) {
		$components = $this->components = $components->getAll();
		sort($components);

		$style = str_replace("/", "-", str_replace("style.css", "", $path));
		$comps = implode("-", $components);

		$this->file = CACHE_DIR . $style . (empty($comps) ? "" : "_" . $comps) . ".css";
	}

	public function get() {
		return @file_get_contents($this->file);
	}

	public function set($style) {
		file_put_contents($this->file, $style);
	}

	public function getTime() {
		if(file_exists($this->file)) {
			return filemtime($this->file);
		} else {
			return time();
		}
	}
}

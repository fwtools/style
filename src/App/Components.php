<?php

namespace App;

class Components {
	private $components;
	private $injector;
	private $passedComponents;

	public function __construct (array $components, \Auryn\Injector $injector) {
		$this->passedComponents = $components;
		$this->components = array_intersect(array_map(function ($item) { return substr(array_reverse(explode("/", $item))[0], 0, strrpos($item, ".") - strlen($item)); }, glob(__DIR__."/Component/*.php")), $components);
		$this->injector = $injector;
	}

	public function getAll () {
		return $this->passedComponents;
	}

	public function getStyle ($component) {
		return $this->injector->make("App\\Component\\$component")->get();
	}

	public function getAllStyles () {
		foreach ($this->components as $component) {
			yield $this->getStyle($component);
		}
	}
}

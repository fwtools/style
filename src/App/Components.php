<?php

namespace App;

class Components {
	private $components;
	private $world;
	private $injector;

	public function __construct (array $components, $world, \Auryn\Injector $injector) {
		$this->components = $components;
		$this->world = $world;
		$this->injector = $injector;
	}

	public function getAll () {
		return $this->components;
	}

	public function getStyle ($component) {
		return $this->injector->make("App\\Component\\$component")->get($this->world);
	}

	public function getAllStyles () {
		foreach ($this->components as $component) {
			yield $this->getStyle($component);
		}
	}
}

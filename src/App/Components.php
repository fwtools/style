<?php

namespace App;

class Components {
	private $components;
	private $injector;

	public function __construct (array $components, \Auryn\Injector $injector) {
		$this->components = $components;
		$this->injector = $injector;
	}

	public function getAll () {
		return $this->components;
	}

	public function getStyle ($component) {
		return $this->injector->make("App/Components/$component")->get();
	}

	public function getAllStyles () {
		foreach ($this->components as $component) {
			yield $this->getStyle($component);
		}
	}
}
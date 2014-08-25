<?php

namespace App\Component;

class npcs implements \App\Component {
	private $db;
	private $request;

	public function __construct (\PDO $db, \Arya\Request $request) {
		$this->db = $db;
		$this->request = $request;
	}

	public function get () {
		return "@import '/npcs.css' all and (width: 200px) and (height: 295px);";
	}
}

<?php

namespace App\Component;

class event implements \App\Component {
	private $db;

	public function __construct (\PDO $db) {
		$this->db = $db;
	}

	public function get ($world) {
		return "#x54y113 a[href='main.php?arrive_eval=drinkwater']:after {
			width: 0;
			height: 0;
			display: inline-block;
			content: url('/event/record?event=pensal-available&world={$world}');
		}";
	}
}

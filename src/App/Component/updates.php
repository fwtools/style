<?php

namespace App\Component;

class updates implements \App\Component {
	private $db;

	public function __construct (\PDO $db) {
		$this->db = $db;
	}

	public function get ($world) {
		$q = $this->db->query("SELECT title FROM fw_news LIMIT 3");
		$data = $q->fetchAll(\PDO::FETCH_OBJ);

		$selector = ".framebannerbg:before";
		$content = "aktuelle Freewar-Updates\\A ";

		foreach($data as $title) {
			$content.= "{$title}\\A ";
		}

		return "{$selector} { content: '{$content}'; white-space: pre; }";
	}
}

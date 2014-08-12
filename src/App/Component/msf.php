<?php

namespace App\Component;

class msf implements \App\Component {
	private $db;

	public function __construct (\PDO $db) {
		$this->db = $db;
	}

	public function get ($world) {
		$q = $this->db->query("SELECT x, y FROM wiki_place WHERE secure = 1");
		$data = $q->fetchAll(\PDO::FETCH_OBJ);
		$selectors = [];

		foreach($data as $row) {
			$selectors[] = "#MapFrameID1 #mapx{$row->x}y{$row->y}:before";
		}

		return implode(', ', $selectors) . ' { position: absolute; left: 0; right: 0; top: 0; bottom: 0; opacity: .8; content: url(i/secure.png); }';
	}
}

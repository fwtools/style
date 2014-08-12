<?php

namespace App\Component;

class at implements \App\Component {
	private $db;

	public function __construct (\PDO $db) {
		$this->db = $db;
	}

	private function getSingleNpcStyle ($name, $display = null) {
		if(!isset($display)) {
			$display = $name;
		}

		$css = "";

		$query = $this->db->prepare("SELECT pn.x, pn.y FROM wiki_npc AS n, wiki_place_npc AS pn WHERE n.name = ? && n.name = pn.npc");
		$query->execute([$name]);
		$data = $query->fetchAll(\PDO::FETCH_OBJ);

		foreach($data as $p) {
			$css.= "#mapx{$p->x}y{$p->y} > a:after { content: '{$display}'; }";
		}

		return $css;
	}

	public function get () {
		$css = $this->getSingleNpcStyle('Onlo-Skelett', 'Onlo');
		$css.= $this->getSingleNpcStyle('Ektofron');
		$css.= $this->getSingleNpcStyle('Blattalisk');
		$css.= $this->getSingleNpcStyle('Untoter Bürger', 'Bürger');
		$css.= $this->getSingleNpcStyle('temporaler Falter', 'Falter');
		$css.= $this->getSingleNpcStyle('Koloa-Käfer', 'Käfer');

		# Mentoran GZK
		$css.= '.frameitembg select[name="z_pos_id"] option[value="290"] { font-weight: bold; }';

		return $css;
	}
}

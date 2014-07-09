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

		$query = $this->db->prepare("SELECT n.name, pn.x, pn.y FROM wiki_npc AS n, wiki_place_npc AS pn WHERE n.name = ? && n.name = pn.npc ORDER BY n.name");
		$query->execute([$name]);
		$data = $query->fetchAll(\PDO::FETCH_OBJ);

		foreach($data as $p) {
			$css.= "#mapx{$x}y{$y} a:after{content:'{$text}}";
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

		$css.= '.frameitembg select[name="z_pos_id"] option[value="290"] { font-weight: bold; }';

		$css.= 'a[href="main.php?arrive_eval=getmission"], a[href="main.php?finish=1"] { display: block; width: 200px; height: 40px; padding: 10px; margin: 10px 0; border: 1px solid rgba(0,0,0,.2); text-align: center; color: #fff; font-size: bigger; background: #27ae60; }';
		$css.= 'a[href="main.php?arrive_eval=getmission"]:hover, a[href="main.php?finish=1"]:hover { color: #fff; background: #2ecc71; }';

		return $css;
	}
}

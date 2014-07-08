<?php

namespace App\Component;

class at implements \App\Component {
	private $db;
	
	public function __construct (\PDO $db) {
		$this->db = $db;
	}
	
	private function helper ($name, $display) {
		$query = $this->db->prepare("SELECT n.name, pn.x, pn.y FROM fw_npc AS n, fw_place_npc AS pn WHERE n.name = ? && n.name = pn.npc ORDER BY n.name");
		$query->execute([$name]);
		$data = $query->fetchAll(\PDO::FETCH_OBJ);
	
		$css = "";
	
		$orte = array();
		foreach($data as $row) {
			if(isset($orte[$row->x][$row->y])) {
				$orte[$row->x][$row->y] .= ' & ' . $display;
			} else {
				$orte[$row->x][$row->y] = $display;
			}
		}
	
		foreach($orte as $x => $arr) {
			foreach($arr as $y => $text) {
				$css .= '#mapx' . $x . 'y' . $y . ' a:after { content: "';
				$css .= str_replace(array('Ä', 'ä', 'Ö', 'ö', 'Ü', 'ü', 'ß'), array('\\0000C4', '\\0000E4', '\\0000D6', '\\0000F6', '\\0000DC', '\\0000FC', '\\0000DF'), $text);
				$css .= '"; opacity: 1; }' . "\n";
			}
		}
	
		return $css;
	}

	public function get () {
		$css = $this->helper('Onlo-Skelett',      'Onlo');
		$css.= $this->helper('Ektofron',          'Ektofron');
		$css.= $this->helper('Blattalisk',        'Blattalisk');
		$css.= $this->helper('Untoter Bürger',    'Bürger');
		$css.= $this->helper('temporaler Falter', 'Falter');
		$css.= $this->helper('Koloa-Käfer',       'Käfer');
	
		$css.= '.frameitembg select[name="z_pos_id"] option[value="290"] { font-weight: bold; }';
	
		$css.= 'a[href="main.php?arrive_eval=getmission"], a[href="main.php?finish=1"] { display: block; width: 200px; height: 40px; padding: 10px; margin: 10px 0; border: 1px solid rgba(0,0,0,.2); text-align: center; color: #fff; font-size: bigger; background: #27ae60; }';
		$css.= 'a[href="main.php?arrive_eval=getmission"]:hover, a[href="main.php?finish=1"]:hover { color: #fff; background: #2ecc71; }';
	
		return $css;
	}
}

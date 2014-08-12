<?php

namespace App\Component;

class agg implements \App\Component {
	private $db;

	public function __construct (\PDO $db) {
		$this->db = $db;
	}

	public function get ($world) {
		$q = $this->db->query("SELECT n.attack, pn.x, pn.y FROM wiki_npc AS n, wiki_place_npc AS pn WHERE n.aggressive = 1 && n.name = pn.name && pn.x < 0 && pn.y < 0 ORDER BY n.attack");
		$data = $q->fetchAll(\PDO::FETCH_OBJ);

		$css = "";
		$orte = array();
		$content = array();

		foreach($data as $row) {
			if(isset($orte[$row->x][$row->y])) {
				$orte[$row->x][$row->y] .= ', ' . str_replace('.', '', $row->attack);
			} else {
				$orte[$row->x][$row->y] = str_replace('.', '', $row->attack);
			}
		}

		foreach($orte as $x => $arr) {
			foreach($arr as $y => $text) {
				$text = str_replace(['oder', '-'], ',', $text);
				$vals = explode(',', $text);
				$min = -1;
				$max = -1;

				foreach($vals as $val) {
					$val = (int) trim($val);

					if($val === 0)
						continue;

					if($min === -1 || $val < $min) {
						$min = $val;
					}

					if($max === -1 || $val > $max) {
						$max = $val;
					}
				}

				if($min === $max) {
					$text = $min;
				} else {
					$text = "$min-$max";
				}

				$content[$text][] = "#mapx{$x}y{$y} > a:after";
			}
		}

		foreach($content as $text => $selects) {
			$css.= implode(', ', $selects) . " { content: '{$text}'; }";
		}

		return $css . ".framemapbg td { color: red; opacity: 1; }";
	}
}

<?php

namespace App;

use \Arya\Request as Request;
use \Arya\Response as Response;

class Track {
	private $db;

	public function __construct(\PDO $db) {
		$this->db = $db;
	}

	public function addRecord(Request $request, $id, $x, $y) {
		$response = new Response;

		$query = $this->db->prepare("SELECT x, y FROM style_track WHERE id = ? ORDER BY time DESC LIMIT 1");
		$query->execute([md5($id)]);
		$data = $query->fetch(\PDO::FETCH_OBJ);

		if(!$query->rowCount() || $x != $data->x || $y != $data->y) {
			$query = $this->db->prepare("INSERT INTO style_track (id, x, y, time) VALUES(?, ?, ?, NOW())");
			$query->execute([md5($id), $x, $y]);
		}

		$exp_gmt = gmdate("D, d M Y H:i:s", time() + 1) . " GMT";
		$mod_gmt = gmdate("D, d M Y H:i:s", time()) . " GMT";

		$response->setHeader('Expires', $exp_gmt);
		$response->setHeader('Last-Modified', $mod_gmt);
		$response->setHeader('Cache-Control', 'private, max-age=1');
		$response->addHeader('Cache-Control', 'post-check=1');
		$response->setBody('');

		return $response;
	}

	public function css(Request $request) {
		$response = new Response;

		$time = 240;

		$exp_gmt = gmdate("D, d M Y H:i:s", time() + $time * 60) . " GMT";
		$mod_gmt = gmdate("D, d M Y H:i:s", time()) . " GMT";

		$response->setHeader('Expires', $exp_gmt);
		$response->setHeader('Last-Modified', $mod_gmt);
		$response->setHeader('Cache-Control', 'private, max-age=' . (60 * $time));
		$response->addHeader('Cache-Control', 'post-check=' . (60 * $time - 10));

		$hash = md5($request['QUERY_STRING']);

		if(file_exists(__DIR__ . "/../../gen/track_{$hash}.css")) {
			return $response->setBody(file_get_contents(__DIR__ . "/../../gen/track_{$hash}.css"));
		}

		$q = $this->db->query("SELECT x, y FROM wiki_place WHERE x > 0 && y > 0");
		$data = $q->fetchAll(\PDO::FETCH_OBJ);
		$css = "";

		foreach($data as $row) {
			$css.= "#mainmapx{$row->x}y{$row->y}{background:url('/track/record/{$hash}/{$row->x}/{$row->y}')}";
		}

		$css.= ".imageborder{background-color: #fafafa !important}";

		file_put_contents(__DIR__ . "/../../gen/track_{$hash}.css", $css);

		return $response->setBody($css);
	}

	public function singleUserMap(Request $request, $id) {
		$id = md5(md5(md5($id)));

		return $this->map($request, $id);
	}

	public function multiUserMap(Request $request) {
		return $this->map($request);
	}

	private function map(Request $request, $id = null) {
		$q = $this->db->query("SELECT area, x, y, secure FROM wiki_place WHERE x > 0 && y > 0");
		$data = $q->fetchAll(\PDO::FETCH_OBJ);

		$secure = [];
		$mapinfo = [
			'x.min' => PHP_INT_MAX,
			'x.max' => 0,
			'y.min' => PHP_INT_MAX,
			'y.max' => 0,
		];

		foreach($data as $row) {
			if($row->area === 'Narubia') {
				$row->x -= 365;
				$row->y += 25;
			}

			else if($row->area === 'Itolos') {
				$row->x -= 116;
				$row->y -= 5;
			}

			else if($row->area === 'Düsterfrostinsel') {
				$row->x -= 652;
				$row->y -= 725;
			}

			else if($row->area === 'Belpharia - Die Hauptinsel' || $row->area === 'Belpharia - Die Westinsel' || $row->area === 'Belpharia - Die Ostinsel') {
				$row->x -= 54;
				$row->y -= 31;
			}

			else if($row->area === 'Gefrorene Insel') {
				$row->x -= 858;
				$row->y -= 890;
			}

			$mapinfo['x.min'] = min($mapinfo['x.min'], $row->x);
			$mapinfo['x.max'] = max($mapinfo['x.max'], $row->x);
			$mapinfo['y.min'] = min($mapinfo['y.min'], $row->y);
			$mapinfo['y.max'] = max($mapinfo['y.max'], $row->y);

			$secure[$row->x][$row->y] = $row->secure;
		}

		$place = [];

		if(isset($id)) {
			$q = $this->db->prepare("SELECT x, y, COUNT(1) as cnt FROM style_track WHERE id = ? GROUP BY x, y");
			$q->execute([$id]);
		} else {
			$q = $this->db->query("SELECT x, y, COUNT(1) as cnt FROM style_track GROUP BY x, y");
		}

		$maxCnt = 1;
		$data = $q->fetchAll(\PDO::FETCH_OBJ);

		foreach($data as $row) {
			if(!isset($secure[$row->x][$row->y]))
				continue;

			$place[$row->x][$row->y] = $row->cnt;

			if($secure[$row->x][$row->y] == 0 && $place[$row->x][$row->y] > $maxCnt) {
				$maxCnt = $place[$row->x][$row->y];
			}
		}

		$map = ImageCreateFromPNG(__DIR__ . '/../../assets/freewar/map.png');

		for($x = $mapinfo['x.min'] - 1; $x <= $mapinfo['x.max'] + 1; $x++) {
			for($y = $mapinfo['y.min'] - 1; $y <= $mapinfo['y.max'] + 1; $y++) {
				if(isset($secure[$x][$y])) {
					if(!isset($place[$x][$y]))
						$place[$x][$y] = 0;

					$white = ImageColorAllocateAlpha($map, 0, 0, 0, (int) min((127 * (($place[$x][$y] / $maxCnt) * .9 + .1)), 127));
					ImageFilledRectangle($map, ($x - $mapinfo['x.min'] + 2) * 10, ($y - $mapinfo['y.min'] + 2) * 10,
							($x - $mapinfo['x.min'] + 3) * 10 - 1, ($y - $mapinfo['y.min'] + 3) * 10 - 1, $white);
				}
			}
		}

		$response = new Response;
		$response->setHeader('Content-Type', 'image/png');

		ob_start();
		ImagePNG($map);
		return $response->setBody(ob_get_clean());
	}
}

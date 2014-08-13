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
		$query->execute([$id]);
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

	public function map(Request $request, $id) {
		$id = md5(md5(md5($id)));

		$q = $this->db->query("SELECT x, y, secure FROM wiki_place WHERE x > 0 && y > 0 && x < 150 && y < 130");
		$data = $q->fetchAll(\PDO::FETCH_OBJ);

		$secure = [];
		foreach($data as $row) {
			$secure[$row->x][$row->y] = $row->secure;
		}

		$place = [];

		$query = $this->db->prepare("SELECT x, y, COUNT(1) as cnt FROM style_track WHERE id = ? GROUP BY x, y");
		$query->execute([$id]);

		$maxCnt = 1;
		$data = $query->fetchAll(\PDO::FETCH_OBJ);

		foreach($data as $row) {
			$place[$row->x][$row->y] = $row->cnt;

			if($secure[$row->x][$row->y] == 0 && $place[$row->x][$row->y] > $maxCnt) {
				$maxCnt = $place[$row->x][$row->y];
			}
		}

		$q = $this->db->query("SELECT min(x) AS min_x, min(y) AS min_y, max(x) AS max_x, max(y) AS max_y FROM wiki_place WHERE x > 0 && y > 0 && x < 150 && y < 130");
		$data = $q->fetchAll(\PDO::FETCH_OBJ);

		$min_x = $data[0]->min_x;
		$min_y = $data[0]->min_y;
		$max_x = $data[0]->max_x;
		$max_y = $data[0]->max_y;

		$map = ImageCreateFromPNG(__DIR__ . '/../../assets/freewar/map.png');

		for($x = $min_x - 1; $x <= $max_x + 1; $x++) {
			for($y = $min_y - 1; $y <= $max_y + 1; $y++) {
				if(isset($secure[$x][$y])) {
					if(!isset($place[$x][$y]))
						$place[$x][$y] = 0;

					$white = ImageColorAllocateAlpha($map, 0, 0, 0, (int) min((127 * (($place[$x][$y]/$maxCnt) * .9 + .1)), 127));
					ImageFilledRectangle($map, ($x-$min_x+2) * 10, ($y-$min_y+2) * 10, ($x-$min_x+3) * 10 - 1, ($y-$min_y+3) * 10 - 1, $white);
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

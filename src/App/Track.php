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
		$query = $db->prepare("SELECT x, y FROM style_track WHERE id = ? ORDER BY time DESC LIMIT 1");
		$query->execute([$id]);
		$data = $query->fetch(\PDO::FETCH_OBJ);

		if(!$query->rowCount() || $x != $data->x || $y != $data->y) {
			$query = $db->prepare("INSERT INTO style_track (id, x, y, time) VALUES(?, ?, ?, NOW())");
			$query->execute([$id, $x, $y]);
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

		$q = $db->query("SELECT x, y FROM wiki_place WHERE x > 0 && y > 0");
		$data = $q->fetchAll(\PDO::FETCH_OBJ);
		$css = "";

		foreach($data as $row) {
			$css.= "#mainmapx{$row->x}y{$row->y}{background:url('/track/record/{$hash}/{$row->x}/{$row->y}')}";
		}

		$css.= ".imageborder{background-color: #fafafa !important}";

		file_put_contents(__DIR__ . "/../../gen/track_{$hash}.css", $css);

		return $response->setBody($css);
	}
}
